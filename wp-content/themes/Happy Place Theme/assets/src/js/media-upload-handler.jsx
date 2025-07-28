import React, { useState, useRef, useCallback } from 'react';
import { Upload, X, Edit3, Eye, Star, Image, Video, FileText } from 'lucide-react';

const MediaUploadHandler = ({ 
    onMediaChange, 
    initialMedia = [], 
    maxFiles = 100,
    acceptedTypes = ['image/*'],
    maxFileSize = 10, // MB
    categories = ['Exterior', 'Living Areas', 'Kitchen', 'Bedrooms', 'Bathrooms', 'Other Spaces']
}) => {
    const [media, setMedia] = useState(initialMedia);
    const [dragOver, setDragOver] = useState(false);
    const [uploading, setUploading] = useState([]);
    const [previewImage, setPreviewImage] = useState(null);
    const [currentCategory, setCurrentCategory] = useState('All');
    const [sortBy, setSortBy] = useState('upload_order');
    const [selectedFiles, setSelectedFiles] = useState([]);
    const [showBulkActions, setShowBulkActions] = useState(false);

    const fileInputRef = useRef(null);
    const dragCounterRef = useRef(0);

    // Get WordPress config
    const config = window.hphFormConfig || {};

    // File validation
    const validateFile = useCallback((file) => {
        const errors = [];
        
        // Size validation
        if (file.size > maxFileSize * 1024 * 1024) {
            errors.push(`File size must be less than ${maxFileSize}MB`);
        }
        
        // Type validation
        const isValidType = acceptedTypes.some(type => {
            if (type.endsWith('/*')) {
                return file.type.startsWith(type.slice(0, -1));
            }
            return file.type === type;
        });
        
        if (!isValidType) {
            errors.push('File type not supported');
        }
        
        return errors;
    }, [acceptedTypes, maxFileSize]);

    // Process files for upload
    const processFiles = useCallback(async (files) => {
        const validFiles = [];
        const fileArray = Array.from(files);
        
        for (const file of fileArray) {
            const errors = validateFile(file);
            
            if (errors.length === 0) {
                const fileData = {
                    id: `temp_${Date.now()}_${Math.random()}`,
                    file,
                    name: file.name,
                    size: file.size,
                    type: file.type,
                    category: 'Uncategorized',
                    isFeatured: false,
                    uploadProgress: 0,
                    preview: null,
                    errors: [],
                    wordpress_id: null // Will be set after upload to WordPress
                };
                
                // Generate preview for images
                if (file.type.startsWith('image/')) {
                    try {
                        fileData.preview = await generatePreview(file);
                    } catch (error) {
                        console.error('Error generating preview:', error);
                    }
                }
                
                validFiles.push(fileData);
            }
        }
        
        return validFiles;
    }, [validateFile]);

    // Generate image preview
    const generatePreview = (file) => {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (e) => resolve(e.target.result);
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    };

    // Upload to WordPress Media Library
    const uploadToWordPress = useCallback(async (fileData) => {
        try {
            const formData = new FormData();
            formData.append('action', 'hph_upload_media');
            formData.append('nonce', config.nonce);
            formData.append('file', fileData.file);
            formData.append('category', fileData.category);
            formData.append('is_featured', fileData.isFeatured ? '1' : '0');

            const response = await fetch(config.ajaxUrl, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                // Update file data with WordPress attachment ID and URL
                setMedia(prev => prev.map(item => 
                    item.id === fileData.id 
                        ? { 
                            ...item, 
                            wordpress_id: result.data.attachment_id,
                            url: result.data.url,
                            uploadProgress: 100, 
                            uploaded: true 
                        }
                        : item
                ));

                return result.data;
            } else {
                throw new Error(result.data?.message || 'Upload failed');
            }

        } catch (error) {
            // Update file with error state
            setMedia(prev => prev.map(item => 
                item.id === fileData.id 
                    ? { ...item, errors: [error.message], uploadProgress: 0 }
                    : item
            ));
            
            console.error('WordPress upload failed:', error);
            return null;
        }
    }, [config]);

    // Simulate upload progress and handle WordPress upload
    const handleUpload = useCallback(async (fileData) => {
        const updateProgress = (progress) => {
            setMedia(prev => prev.map(item => 
                item.id === fileData.id 
                    ? { ...item, uploadProgress: progress }
                    : item
            ));
        };

        // Simulate initial progress
        for (let progress = 0; progress <= 80; progress += 20) {
            await new Promise(resolve => setTimeout(resolve, 100));
            updateProgress(progress);
        }

        // Actually upload to WordPress
        const uploadResult = await uploadToWordPress(fileData);
        
        if (uploadResult) {
            updateProgress(100);
        }

        setUploading(prev => prev.filter(id => id !== fileData.id));
    }, [uploadToWordPress]);

    // Handle file selection
    const handleFileSelect = useCallback(async (files) => {
        if (media.length + files.length > maxFiles) {
            alert(`Maximum ${maxFiles} files allowed`);
            return;
        }

        const processedFiles = await processFiles(files);
        
        setMedia(prev => [...prev, ...processedFiles]);
        
        // Start upload for each file
        processedFiles.forEach(fileData => {
            setUploading(prev => [...prev, fileData.id]);
            handleUpload(fileData);
        });

        // Trigger callback
        const newMediaList = [...media, ...processedFiles];
        onMediaChange?.(newMediaList);
    }, [media, maxFiles, processFiles, handleUpload, onMediaChange]);

    // Drag and drop handlers
    const handleDragEnter = useCallback((e) => {
        e.preventDefault();
        dragCounterRef.current++;
        setDragOver(true);
    }, []);

    const handleDragLeave = useCallback((e) => {
        e.preventDefault();
        dragCounterRef.current--;
        if (dragCounterRef.current === 0) {
            setDragOver(false);
        }
    }, []);

    const handleDragOver = useCallback((e) => {
        e.preventDefault();
    }, []);

    const handleDrop = useCallback((e) => {
        e.preventDefault();
        setDragOver(false);
        dragCounterRef.current = 0;
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFileSelect(files);
        }
    }, [handleFileSelect]);

    // Remove media item
    const removeMedia = useCallback(async (id) => {
        const mediaItem = media.find(item => item.id === id);
        
        // If uploaded to WordPress, delete from media library
        if (mediaItem?.wordpress_id) {
            try {
                const formData = new FormData();
                formData.append('action', 'hph_delete_media');
                formData.append('nonce', config.nonce);
                formData.append('attachment_id', mediaItem.wordpress_id);

                await fetch(config.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });
            } catch (error) {
                console.error('Error deleting from WordPress:', error);
            }
        }

        setMedia(prev => {
            const updated = prev.filter(item => item.id !== id);
            onMediaChange?.(updated);
            return updated;
        });
        setSelectedFiles(prev => prev.filter(fileId => fileId !== id));
    }, [media, config, onMediaChange]);

    // Update media item
    const updateMedia = useCallback((id, updates) => {
        setMedia(prev => {
            const updated = prev.map(item => 
                item.id === id ? { ...item, ...updates } : item
            );
            onMediaChange?.(updated);
            return updated;
        });
    }, [onMediaChange]);

    // Set featured image
    const setFeatured = useCallback((id) => {
        setMedia(prev => {
            const updated = prev.map(item => ({
                ...item,
                isFeatured: item.id === id
            }));
            onMediaChange?.(updated);
            return updated;
        });
    }, [onMediaChange]);

    // Filter media by category
    const filteredMedia = media.filter(item => {
        if (currentCategory === 'All') return true;
        if (currentCategory === 'Uncategorized') return !item.category || item.category === 'Uncategorized';
        return item.category === currentCategory;
    });

    // Sort media
    const sortedMedia = [...filteredMedia].sort((a, b) => {
        switch (sortBy) {
            case 'name':
                return a.name.localeCompare(b.name);
            case 'size':
                return b.size - a.size;
            case 'type':
                return a.type.localeCompare(b.type);
            case 'upload_order':
            default:
                return 0; // Maintain original order
        }
    });

    // Toggle file selection
    const toggleFileSelection = (id) => {
        setSelectedFiles(prev => {
            const isSelected = prev.includes(id);
            const updated = isSelected 
                ? prev.filter(fileId => fileId !== id)
                : [...prev, id];
            
            setShowBulkActions(updated.length > 0);
            return updated;
        });
    };

    // Bulk actions
    const bulkUpdateCategory = (category) => {
        selectedFiles.forEach(id => {
            updateMedia(id, { category });
        });
        setSelectedFiles([]);
        setShowBulkActions(false);
    };

    const bulkDelete = () => {
        if (confirm(`Delete ${selectedFiles.length} selected files?`)) {
            selectedFiles.forEach(id => removeMedia(id));
            setSelectedFiles([]);
            setShowBulkActions(false);
        }
    };

    const getFileIcon = (type) => {
        if (type.startsWith('image/')) return <Image size={16} />;
        if (type.startsWith('video/')) return <Video size={16} />;
        return <FileText size={16} />;
    };

    const formatFileSize = (bytes) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    return (
        <div className="hph-media-upload-handler">
            {/* Upload Area */}
            <div 
                className={`hph-upload-zone ${dragOver ? 'drag-over' : ''}`}
                onDragEnter={handleDragEnter}
                onDragLeave={handleDragLeave}
                onDragOver={handleDragOver}
                onDrop={handleDrop}
                onClick={() => fileInputRef.current?.click()}
            >
                <div className="hph-upload-content">
                    <Upload size={48} />
                    <h3>Drop files here or <span className="upload-link">browse</span></h3>
                    <p>Upload images and videos for your listing</p>
                    <div className="upload-specs">
                        <span>Max {maxFileSize}MB per file</span>
                        <span>•</span>
                        <span>Up to {maxFiles} files</span>
                    </div>
                </div>
                
                <input
                    ref={fileInputRef}
                    type="file"
                    multiple
                    accept={acceptedTypes.join(',')}
                    onChange={(e) => handleFileSelect(e.target.files)}
                    className="hph-file-input"
                />
            </div>

            {/* Media Controls */}
            {media.length > 0 && (
                <div className="hph-media-controls">
                    <div className="hph-media-stats">
                        <span className="media-count">{media.length} files</span>
                        <span className="featured-count">
                            {media.filter(item => item.isFeatured).length} featured
                        </span>
                    </div>

                    <div className="hph-media-filters">
                        <div className="filter-group">
                            <label>Category:</label>
                            <select 
                                value={currentCategory} 
                                onChange={(e) => setCurrentCategory(e.target.value)}
                                className="hph-filter-select"
                            >
                                <option value="All">All Categories</option>
                                <option value="Uncategorized">Uncategorized</option>
                                {categories.map(cat => (
                                    <option key={cat} value={cat}>{cat}</option>
                                ))}
                            </select>
                        </div>
                        
                        <div className="filter-group">
                            <label>Sort by:</label>
                            <select 
                                value={sortBy} 
                                onChange={(e) => setSortBy(e.target.value)}
                                className="hph-filter-select"
                            >
                                <option value="upload_order">Upload Order</option>
                                <option value="name">Name</option>
                                <option value="size">File Size</option>
                                <option value="type">File Type</option>
                            </select>
                        </div>
                    </div>
                </div>
            )}

            {/* Bulk Actions */}
            {showBulkActions && (
                <div className="hph-bulk-actions">
                    <div className="bulk-info">
                        {selectedFiles.length} files selected
                    </div>
                    <div className="bulk-buttons">
                        <select 
                            onChange={(e) => e.target.value && bulkUpdateCategory(e.target.value)}
                            className="bulk-category-select"
                            defaultValue=""
                        >
                            <option value="">Set Category...</option>
                            {categories.map(cat => (
                                <option key={cat} value={cat}>{cat}</option>
                            ))}
                        </select>
                        <button onClick={bulkDelete} className="hph-btn hph-btn--sm hph-btn--danger">
                            Delete Selected
                        </button>
                    </div>
                </div>
            )}

            {/* Media Grid */}
            {sortedMedia.length > 0 && (
                <div className="hph-media-grid">
                    {sortedMedia.map((item) => (
                        <MediaItem
                            key={item.id}
                            item={item}
                            isSelected={selectedFiles.includes(item.id)}
                            isUploading={uploading.includes(item.id)}
                            categories={categories}
                            onToggleSelect={() => toggleFileSelection(item.id)}
                            onRemove={() => removeMedia(item.id)}
                            onUpdate={(updates) => updateMedia(item.id, updates)}
                            onSetFeatured={() => setFeatured(item.id)}
                            onPreview={() => setPreviewImage(item)}
                            getFileIcon={getFileIcon}
                            formatFileSize={formatFileSize}
                        />
                    ))}
                </div>
            )}

            {/* Image Preview Modal */}
            {previewImage && (
                <ImagePreviewModal
                    image={previewImage}
                    onClose={() => setPreviewImage(null)}
                    onUpdate={(updates) => updateMedia(previewImage.id, updates)}
                />
            )}
        </div>
    );
};

// Individual Media Item Component
const MediaItem = ({ 
    item, 
    isSelected, 
    isUploading, 
    categories, 
    onToggleSelect, 
    onRemove, 
    onUpdate, 
    onSetFeatured, 
    onPreview,
    getFileIcon,
    formatFileSize
}) => {
    const [showActions, setShowActions] = useState(false);
    const [editingName, setEditingName] = useState(false);
    const [newName, setNewName] = useState(item.name);

    const handleNameSave = () => {
        if (newName.trim() && newName !== item.name) {
            onUpdate({ name: newName.trim() });
        }
        setEditingName(false);
    };

    return (
        <div 
            className={`hph-media-item ${isSelected ? 'selected' : ''} ${isUploading ? 'uploading' : ''}`}
            onMouseEnter={() => setShowActions(true)}
            onMouseLeave={() => setShowActions(false)}
        >
            {/* Selection Checkbox */}
            <div className="media-item-select">
                <input
                    type="checkbox"
                    checked={isSelected}
                    onChange={onToggleSelect}
                    className="media-checkbox"
                />
            </div>

            {/* Featured Badge */}
            {item.isFeatured && (
                <div className="featured-badge">
                    <Star size={14} fill="currentColor" />
                    Featured
                </div>
            )}

            {/* Media Preview */}
            <div className="media-preview" onClick={onPreview}>
                {item.preview ? (
                    <img src={item.preview} alt={item.name} />
                ) : (
                    <div className="media-placeholder">
                        {getFileIcon(item.type)}
                        <span>{item.type.split('/')[1]?.toUpperCase()}</span>
                    </div>
                )}
                
                {/* Upload Progress */}
                {isUploading && (
                    <div className="upload-progress">
                        <div className="progress-bar">
                            <div 
                                className="progress-fill" 
                                style={{ width: `${item.uploadProgress}%` }}
                            />
                        </div>
                        <span className="progress-text">
                            {item.uploadProgress}% uploaded
                        </span>
                    </div>
                )}

                {/* Error State */}
                {item.errors && item.errors.length > 0 && (
                    <div className="upload-error">
                        <X size={16} />
                        <span>{item.errors[0]}</span>
                    </div>
                )}
            </div>

            {/* Media Info */}
            <div className="media-info">
                {editingName ? (
                    <div className="name-edit">
                        <input
                            type="text"
                            value={newName}
                            onChange={(e) => setNewName(e.target.value)}
                            onBlur={handleNameSave}
                            onKeyPress={(e) => e.key === 'Enter' && handleNameSave()}
                            className="name-input"
                            autoFocus
                        />
                    </div>
                ) : (
                    <div 
                        className="media-name" 
                        onClick={() => setEditingName(true)}
                        title="Click to edit name"
                    >
                        {item.name}
                    </div>
                )}
                
                <div className="media-meta">
                    <span>{formatFileSize(item.size)}</span>
                    <span>{item.type.split('/')[1]?.toUpperCase()}</span>
                </div>

                <div className="media-category">
                    <select
                        value={item.category || 'Uncategorized'}
                        onChange={(e) => onUpdate({ category: e.target.value })}
                        className="category-select"
                    >
                        <option value="Uncategorized">Uncategorized</option>
                        {categories.map(cat => (
                            <option key={cat} value={cat}>{cat}</option>
                        ))}
                    </select>
                </div>
            </div>

            {/* Actions Overlay */}
            {showActions && !isUploading && !item.errors?.length && (
                <div className="media-actions">
                    <button
                        className={`action-btn ${item.isFeatured ? 'action-btn--primary' : 'action-btn--secondary'}`}
                        onClick={() => onSetFeatured()}
                        title="Set as featured"
                    >
                        <Star size={14} fill={item.isFeatured ? "currentColor" : "none"} />
                    </button>
                    <button
                        className="action-btn action-btn--secondary"
                        onClick={onPreview}
                        title="Preview"
                    >
                        <Eye size={14} />
                    </button>
                    <button
                        className="action-btn action-btn--secondary"
                        onClick={() => setEditingName(true)}
                        title="Edit"
                    >
                        <Edit3 size={14} />
                    </button>
                    <button
                        className="action-btn action-btn--danger"
                        onClick={onRemove}
                        title="Remove"
                    >
                        <X size={14} />
                    </button>
                </div>
            )}
        </div>
    );
};

// Image Preview Modal Component
const ImagePreviewModal = ({ image, onClose, onUpdate }) => {
    const [editingName, setEditingName] = useState(false);
    const [newName, setNewName] = useState(image.name);

    const handleSave = () => {
        onUpdate({ name: newName });
        onClose();
    };

    return (
        <div className="hph-preview-modal" onClick={onClose}>
            <div className="modal-content" onClick={(e) => e.stopPropagation()}>
                <div className="modal-header">
                    <h3>Image Preview</h3>
                    <button onClick={onClose} className="close-btn">
                        <X size={20} />
                    </button>
                </div>

                <div className="modal-body">
                    <div className="preview-image">
                        <img src={image.preview || image.url} alt={image.name} />
                    </div>
                    
                    <div className="image-details">
                        <div className="detail-group">
                            <label>File Name:</label>
                            {editingName ? (
                                <input
                                    type="text"
                                    value={newName}
                                    onChange={(e) => setNewName(e.target.value)}
                                    onBlur={() => setEditingName(false)}
                                    className="name-input"
                                    autoFocus
                                />
                            ) : (
                                <span onClick={() => setEditingName(true)} className="editable-name">
                                    {image.name}
                                </span>
                            )}
                        </div>
                        
                        <div className="detail-group">
                            <label>Category:</label>
                            <span>{image.category || 'Uncategorized'}</span>
                        </div>
                        
                        <div className="detail-group">
                            <label>File Size:</label>
                            <span>{(image.size / 1024 / 1024).toFixed(2)} MB</span>
                        </div>
                    </div>
                </div>

                <div className="modal-footer">
                    <button onClick={onClose} className="hph-btn hph-btn--outline">
                        Close
                    </button>
                    <button onClick={handleSave} className="hph-btn hph-btn--primary">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    );
};

export default MediaUploadHandler;
