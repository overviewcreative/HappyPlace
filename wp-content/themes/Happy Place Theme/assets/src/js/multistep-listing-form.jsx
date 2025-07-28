import React, { useState, useEffect, useRef, useCallback } from 'react';
import { 
    Home, 
    FileText, 
    Check, 
    Upload, 
    DollarSign, 
    Eye, 
    ArrowLeft, 
    ArrowRight,
    Save,
    MapPin,
    Star,
    Edit3,
    X,
    Image,
    Video
} from 'lucide-react';

// Import the MediaUploadHandler component
import MediaUploadHandler from './media-upload-handler';

const MultiStepListingForm = () => {
    const [currentStep, setCurrentStep] = useState(1);
    const [formData, setFormData] = useState({
        // Step 1: Basic Property Info
        property_type: '',
        street_number: '',
        street_name: '',
        unit_number: '',
        city: '',
        state: 'DE',
        zip_code: '',
        county: '',
        
        // Step 2: Property Details
        bedrooms: '',
        bathrooms: '',
        square_footage: '',
        lot_size: '',
        year_built: '',
        garage_spaces: '',
        full_baths: '',
        half_baths: '',
        stories: '',
        
        // Step 3: Features & Amenities
        interior_features: [],
        exterior_features: [],
        utility_features: [],
        custom_features: [],
        
        // Step 4: Media & Marketing
        property_images: [],
        property_description: '',
        listing_remarks: '',
        showing_instructions: '',
        
        // Step 5: Pricing & Availability
        price: '',
        list_date: '',
        listing_status: 'draft',
        mls_number: '',
        property_tax: '',
        hoa_fees: ''
    });

    const [errors, setErrors] = useState({});
    const [isLoading, setIsLoading] = useState(false);
    const [lastSaved, setLastSaved] = useState(null);
    const [isDirty, setIsDirty] = useState(false);
    const addressInputRef = useRef(null);
    const autocompleteRef = useRef(null);

    // Get WordPress config from global
    const config = window.hphFormConfig || {};
    const fieldMappings = config.fieldMappings || {};

    const steps = [
        { 
            id: 1, 
            title: 'Property Basics', 
            icon: Home, 
            description: 'Type, location & basic info',
            fields: ['property_type', 'street_number', 'street_name', 'city', 'state', 'zip_code']
        },
        { 
            id: 2, 
            title: 'Property Details', 
            icon: FileText, 
            description: 'Size, age & specifications',
            fields: ['bedrooms', 'bathrooms', 'square_footage', 'lot_size', 'year_built']
        },
        { 
            id: 3, 
            title: 'Features', 
            icon: Check, 
            description: 'Amenities & special features',
            fields: ['interior_features', 'exterior_features', 'utility_features']
        },
        { 
            id: 4, 
            title: 'Media', 
            icon: Upload, 
            description: 'Photos & descriptions',
            fields: ['property_images', 'property_description', 'listing_remarks']
        },
        { 
            id: 5, 
            title: 'Pricing', 
            icon: DollarSign, 
            description: 'Price & availability',
            fields: ['price', 'list_date', 'listing_status']
        },
        { 
            id: 6, 
            title: 'Review', 
            icon: Eye, 
            description: 'Review & publish',
            fields: []
        }
    ];

    // Initialize form data from WordPress
    useEffect(() => {
        const rootEl = document.getElementById('hph-multistep-form-root');
        if (rootEl) {
            const listingDataAttr = rootEl.getAttribute('data-listing-data');
            if (listingDataAttr) {
                try {
                    const listingData = JSON.parse(listingDataAttr);
                    if (listingData && Object.keys(listingData).length > 0) {
                        setFormData(prev => ({ ...prev, ...listingData }));
                    }
                } catch (error) {
                    console.error('Error parsing listing data:', error);
                }
            }
        }
    }, []);

    // Initialize Google Places Autocomplete
    useEffect(() => {
        if (window.google && window.google.maps && window.google.maps.places && addressInputRef.current) {
            const autocomplete = new window.google.maps.places.Autocomplete(addressInputRef.current, {
                componentRestrictions: { country: 'us' },
                fields: ['address_components', 'formatted_address', 'geometry'],
                types: ['address']
            });

            autocomplete.addListener('place_changed', () => {
                const place = autocomplete.getPlace();
                if (place.address_components) {
                    parseAddress(place.address_components);
                }
            });

            autocompleteRef.current = autocomplete;
        }
    }, []);

    // Parse Google Places address components
    const parseAddress = (components) => {
        const addressMap = {
            street_number: '',
            route: '',
            locality: '',
            administrative_area_level_1: '',
            postal_code: '',
            administrative_area_level_2: ''
        };

        components.forEach(component => {
            const type = component.types[0];
            if (addressMap.hasOwnProperty(type)) {
                addressMap[type] = component.long_name;
            }
        });

        setFormData(prev => ({
            ...prev,
            street_number: addressMap.street_number,
            street_name: addressMap.route,
            city: addressMap.locality,
            state: addressMap.administrative_area_level_1 === 'Delaware' ? 'DE' : 
                   addressMap.administrative_area_level_1 === 'Pennsylvania' ? 'PA' :
                   addressMap.administrative_area_level_1 === 'Maryland' ? 'MD' :
                   addressMap.administrative_area_level_1 === 'New Jersey' ? 'NJ' : 'DE',
            zip_code: addressMap.postal_code,
            county: addressMap.administrative_area_level_2
        }));
        setIsDirty(true);
    };

    // Auto-save draft every 30 seconds
    useEffect(() => {
        if (isDirty) {
            const saveInterval = setInterval(() => {
                saveDraft();
            }, 30000);

            return () => clearInterval(saveInterval);
        }
    }, [isDirty, formData]);

    const saveDraft = async () => {
        try {
            setIsLoading(true);
            
            const saveData = new FormData();
            saveData.append('action', 'hph_save_listing_draft');
            saveData.append('nonce', config.nonce);
            saveData.append('listing_data', JSON.stringify(formData));
            
            if (config.isEditing && config.listingId) {
                saveData.append('listing_id', config.listingId);
            }

            const response = await fetch(config.ajaxUrl, {
                method: 'POST',
                body: saveData
            });

            const result = await response.json();
            
            if (result.success) {
                setLastSaved(new Date().toLocaleTimeString());
                setIsDirty(false);
                
                // Show WordPress dashboard toast if available
                if (window.HphDashboard && window.HphDashboard.showToast) {
                    window.HphDashboard.showToast('Draft saved automatically', 'success');
                }
            }
            
        } catch (error) {
            console.error('Failed to save draft:', error);
        } finally {
            setIsLoading(false);
        }
    };

    const handleInputChange = (name, value) => {
        setFormData(prev => ({ ...prev, [name]: value }));
        setIsDirty(true);
        
        // Clear field error if it exists
        if (errors[name]) {
            setErrors(prev => ({ ...prev, [name]: null }));
        }
    };

    const validateStep = (stepId) => {
        const step = steps.find(s => s.id === stepId);
        const stepErrors = {};
        
        step.fields.forEach(field => {
            if (isRequired(field) && !formData[field]) {
                stepErrors[field] = 'This field is required';
            }
        });

        setErrors(stepErrors);
        return Object.keys(stepErrors).length === 0;
    };

    const isRequired = (field) => {
        const requiredFields = ['property_type', 'street_name', 'city', 'state', 'price'];
        return requiredFields.includes(field);
    };

    const nextStep = () => {
        if (validateStep(currentStep)) {
            if (currentStep < steps.length) {
                setCurrentStep(currentStep + 1);
            }
        }
    };

    const prevStep = () => {
        if (currentStep > 1) {
            setCurrentStep(currentStep - 1);
        }
    };

    const handleSubmit = async (status = 'draft') => {
        try {
            setIsLoading(true);
            
            const submitData = new FormData();
            submitData.append('action', 'hph_save_listing');
            submitData.append('nonce', config.nonce);
            submitData.append('listing_status', status);
            
            // Map form data to WordPress/ACF fields
            Object.keys(formData).forEach(key => {
                const mappedField = fieldMappings[key] || key;
                const value = formData[key];
                
                if (Array.isArray(value)) {
                    submitData.append(mappedField, JSON.stringify(value));
                } else {
                    submitData.append(mappedField, value);
                }
            });
            
            if (config.isEditing && config.listingId) {
                submitData.append('listing_id', config.listingId);
            }

            const response = await fetch(config.ajaxUrl, {
                method: 'POST',
                body: submitData
            });

            const result = await response.json();
            
            if (result.success) {
                // Show success message using WordPress dashboard system
                if (window.HphDashboard && window.HphDashboard.showToast) {
                    window.HphDashboard.showToast(
                        status === 'publish' ? 'Listing published successfully!' : 'Draft saved successfully!', 
                        'success'
                    );
                } else {
                    alert(status === 'publish' ? 'Listing published successfully!' : 'Draft saved successfully!');
                }
                
                // Redirect to listings section
                if (result.data && result.data.redirect) {
                    window.location.href = result.data.redirect;
                } else {
                    // Default redirect to listings section
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.delete('action');
                    currentUrl.searchParams.delete('listing_id');
                    currentUrl.searchParams.set('section', 'listings');
                    window.location.href = currentUrl.toString();
                }
                
            } else {
                // Show error message
                if (window.HphDashboard && window.HphDashboard.showToast) {
                    window.HphDashboard.showToast(
                        result.data?.message || 'Failed to save listing. Please try again.', 
                        'error'
                    );
                } else {
                    alert(result.data?.message || 'Failed to save listing. Please try again.');
                }
            }
            
        } catch (error) {
            console.error('Failed to submit listing:', error);
            
            if (window.HphDashboard && window.HphDashboard.showToast) {
                window.HphDashboard.showToast('Network error. Please try again.', 'error');
            } else {
                alert('Failed to save listing. Please try again.');
            }
        } finally {
            setIsLoading(false);
        }
    };

    const renderStepIndicator = () => (
        <div className="hph-form-steps">
            {steps.map((step, index) => {
                const StepIcon = step.icon;
                const isActive = currentStep === step.id;
                const isCompleted = currentStep > step.id;
                
                return (
                    <div key={step.id} className={`hph-form-step ${isActive ? 'active' : ''} ${isCompleted ? 'completed' : ''}`}>
                        <div className="hph-step-circle">
                            <StepIcon size={16} />
                            {isCompleted && <Check size={12} className="hph-step-check" />}
                        </div>
                        <div className="hph-step-content">
                            <h4>{step.title}</h4>
                            <p>{step.description}</p>
                        </div>
                        {index < steps.length - 1 && <div className="hph-step-connector" />}
                    </div>
                );
            })}
        </div>
    );

    const renderStep1 = () => (
        <div className="hph-form-section">
            <div className="hph-form-section-header">
                <Home className="hph-form-section-icon" />
                <div>
                    <h3>Property Basics</h3>
                    <p>Enter the property type and location information</p>
                </div>
            </div>

            <div className="hph-form-grid hph-form-grid--2-col">
                <div className="hph-form-group">
                    <label className="hph-form-label">Property Type *</label>
                    <select
                        value={formData.property_type}
                        onChange={(e) => handleInputChange('property_type', e.target.value)}
                        className={`hph-form-select ${errors.property_type ? 'error' : ''}`}
                    >
                        <option value="">Select Property Type</option>
                        <option value="single_family">Single Family Home</option>
                        <option value="condo">Condominium</option>
                        <option value="townhome">Townhome</option>
                        <option value="multi_family">Multi-Family</option>
                        <option value="land">Land/Lot</option>
                        <option value="commercial">Commercial</option>
                        <option value="mobile_home">Mobile Home</option>
                        <option value="other">Other</option>
                    </select>
                    {errors.property_type && <span className="hph-form-error">{errors.property_type}</span>}
                </div>

                <div className="hph-form-group hph-form-group--full">
                    <label className="hph-form-label">Street Address *</label>
                    <input
                        ref={addressInputRef}
                        type="text"
                        value={`${formData.street_number} ${formData.street_name}`.trim()}
                        onChange={(e) => {
                            // Handle manual entry
                            const parts = e.target.value.split(' ');
                            const streetNumber = parts.length > 0 && /^\d+$/.test(parts[0]) ? parts[0] : '';
                            const streetName = parts.length > 1 ? parts.slice(streetNumber ? 1 : 0).join(' ') : parts[0] || '';
                            
                            handleInputChange('street_number', streetNumber);
                            handleInputChange('street_name', streetName);
                        }}
                        className={`hph-form-input ${errors.street_name ? 'error' : ''}`}
                        placeholder="123 Main Street"
                    />
                    {errors.street_name && <span className="hph-form-error">{errors.street_name}</span>}
                </div>

                <div className="hph-form-group">
                    <label className="hph-form-label">Unit/Apt Number</label>
                    <input
                        type="text"
                        value={formData.unit_number}
                        onChange={(e) => handleInputChange('unit_number', e.target.value)}
                        className="hph-form-input"
                        placeholder="Apt 2B"
                    />
                </div>

                <div className="hph-form-group">
                    <label className="hph-form-label">City *</label>
                    <input
                        type="text"
                        value={formData.city}
                        onChange={(e) => handleInputChange('city', e.target.value)}
                        className={`hph-form-input ${errors.city ? 'error' : ''}`}
                        placeholder="Wilmington"
                    />
                    {errors.city && <span className="hph-form-error">{errors.city}</span>}
                </div>

                <div className="hph-form-group">
                    <label className="hph-form-label">State *</label>
                    <select
                        value={formData.state}
                        onChange={(e) => handleInputChange('state', e.target.value)}
                        className={`hph-form-select ${errors.state ? 'error' : ''}`}
                    >
                        <option value="DE">Delaware</option>
                        <option value="PA">Pennsylvania</option>
                        <option value="MD">Maryland</option>
                        <option value="NJ">New Jersey</option>
                    </select>
                    {errors.state && <span className="hph-form-error">{errors.state}</span>}
                </div>

                <div className="hph-form-group">
                    <label className="hph-form-label">ZIP Code</label>
                    <input
                        type="text"
                        value={formData.zip_code}
                        onChange={(e) => handleInputChange('zip_code', e.target.value)}
                        className="hph-form-input"
                        placeholder="19803"
                        maxLength="5"
                    />
                </div>

                <div className="hph-form-group">
                    <label className="hph-form-label">County</label>
                    <input
                        type="text"
                        value={formData.county}
                        onChange={(e) => handleInputChange('county', e.target.value)}
                        className="hph-form-input"
                        placeholder="New Castle County"
                    />
                </div>
            </div>
        </div>
    );

    const renderStep2 = () => (
        <div className="hph-form-section">
            <div className="hph-form-section-header">
                <FileText className="hph-form-section-icon" />
                <div>
                    <h3>Property Details</h3>
                    <p>Provide size, age, and property specifications</p>
                </div>
            </div>

            <div className="hph-form-grid hph-form-grid--3-col">
                <div className="hph-form-group">
                    <label className="hph-form-label">Bedrooms</label>
                    <select
                        value={formData.bedrooms}
                        onChange={(e) => handleInputChange('bedrooms', e.target.value)}
                        className="hph-form-select"
                    >
                        <option value="">Select</option>
                        <option value="0">Studio</option>
                        {[1,2,3,4,5,6,7,8,9,10].map(num => (
                            <option key={num} value={num}>{num}</option>
                        ))}
                    </select>
                </div>

                <div className="hph-form-group">
                    <label className="hph-form-label">Bathrooms</label>
                    <select
                        value={formData.bathrooms}
                        onChange={(e) => handleInputChange('bathrooms', e.target.value)}
                        className="hph-form-select"
                    >
                        <option value="">Select</option>
                        {[1,1.5,2,2.5,3,3.5,4,4.5,5,5.5,6,6.5,7,7.5,8,8.5,9,9.5,10].map(num => (
                            <option key={num} value={num}>{num}</option>
                        ))}
                    </select>
                </div>

                <div className="hph-form-group">
                    <label className="hph-form-label">Garage Spaces</label>
                    <select
                        value={formData.garage_spaces}
                        onChange={(e) => handleInputChange('garage_spaces', e.target.value)}
                        className="hph-form-select"
                    >
                        <option value="">Select</option>
                        <option value="0">No Garage</option>
                        {[1,2,3,4,5].map(num => (
                            <option key={num} value={num}>{num}</option>
                        ))}
                    </select>
                </div>

                <div className="hph-form-group">
                    <label className="hph-form-label">Square Footage</label>
                    <input
                        type="number"
                        value={formData.square_footage}
                        onChange={(e) => handleInputChange('square_footage', e.target.value)}
                        className="hph-form-input"
                        placeholder="2,500"
                        min="0"
                    />
                </div>

                <div className="hph-form-group">
                    <label className="hph-form-label">Lot Size (acres)</label>
                    <input
                        type="number"
                        value={formData.lot_size}
                        onChange={(e) => handleInputChange('lot_size', e.target.value)}
                        className="hph-form-input"
                        placeholder="0.25"
                        step="0.01"
                        min="0"
                    />
                </div>

                <div className="hph-form-group">
                    <label className="hph-form-label">Year Built</label>
                    <input
                        type="number"
                        value={formData.year_built}
                        onChange={(e) => handleInputChange('year_built', e.target.value)}
                        className="hph-form-input"
                        placeholder="2010"
                        min="1800"
                        max={new Date().getFullYear() + 1}
                    />
                </div>

                <div className="hph-form-group">
                    <label className="hph-form-label">Full Baths</label>
                    <input
                        type="number"
                        value={formData.full_baths}
                        onChange={(e) => handleInputChange('full_baths', e.target.value)}
                        className="hph-form-input"
                        min="0"
                        max="20"
                    />
                </div>

                <div className="hph-form-group">
                    <label className="hph-form-label">Half Baths</label>
                    <input
                        type="number"
                        value={formData.half_baths}
                        onChange={(e) => handleInputChange('half_baths', e.target.value)}
                        className="hph-form-input"
                        min="0"
                        max="20"
                    />
                </div>

                <div className="hph-form-group">
                    <label className="hph-form-label">Stories</label>
                    <select
                        value={formData.stories}
                        onChange={(e) => handleInputChange('stories', e.target.value)}
                        className="hph-form-select"
                    >
                        <option value="">Select</option>
                        <option value="1">1 Story</option>
                        <option value="1.5">1.5 Stories</option>
                        <option value="2">2 Stories</option>
                        <option value="2.5">2.5 Stories</option>
                        <option value="3">3 Stories</option>
                        <option value="3+">3+ Stories</option>
                    </select>
                </div>
            </div>
        </div>
    );

    const renderStep3 = () => {
        const featureCategories = {
            interior_features: {
                title: 'Interior Features',
                options: ['Hardwood Floors', 'Granite Countertops', 'Stainless Steel Appliances', 'Walk-in Closet', 'Fireplace', 'Vaulted Ceilings', 'Crown Molding', 'Updated Kitchen', 'Master Suite', 'Laundry Room']
            },
            exterior_features: {
                title: 'Exterior Features',
                options: ['Swimming Pool', 'Deck/Patio', 'Fenced Yard', 'Garden', 'Sprinkler System', 'Hot Tub', 'Outdoor Kitchen', 'Balcony', 'Porch', 'Landscaping']
            },
            utility_features: {
                title: 'Utilities & Systems',
                options: ['Central Air', 'Forced Air Heat', 'Gas Heat', 'Electric Heat', 'Solar Panels', 'Security System', 'Intercom', 'Built-in Vacuum', 'Water Softener', 'Generator']
            }
        };

        const toggleFeature = (category, feature) => {
            setFormData(prev => {
                const currentFeatures = prev[category] || [];
                const isSelected = currentFeatures.includes(feature);
                
                return {
                    ...prev,
                    [category]: isSelected
                        ? currentFeatures.filter(f => f !== feature)
                        : [...currentFeatures, feature]
                };
            });
            setIsDirty(true);
        };

        return (
            <div className="hph-form-section">
                <div className="hph-form-section-header">
                    <Check className="hph-form-section-icon" />
                    <div>
                        <h3>Features & Amenities</h3>
                        <p>Select all features that apply to this property</p>
                    </div>
                </div>

                {Object.entries(featureCategories).map(([category, data]) => (
                    <div key={category} className="hph-feature-category">
                        <h4 className="hph-feature-category-title">{data.title}</h4>
                        <div className="hph-feature-grid">
                            {data.options.map(feature => (
                                <label key={feature} className="hph-feature-checkbox">
                                    <input
                                        type="checkbox"
                                        checked={(formData[category] || []).includes(feature)}
                                        onChange={() => toggleFeature(category, feature)}
                                    />
                                    <span className="hph-feature-label">{feature}</span>
                                </label>
                            ))}
                        </div>
                    </div>
                ))}

                <div className="hph-form-group">
                    <label className="hph-form-label">Custom Features</label>
                    <textarea
                        value={(formData.custom_features || []).join('\n')}
                        onChange={(e) => {
                            const features = e.target.value.split('\n').filter(f => f.trim());
                            handleInputChange('custom_features', features);
                        }}
                        className="hph-form-textarea"
                        rows="4"
                        placeholder="Enter additional custom features, one per line..."
                    />
                </div>
            </div>
        );
    };

    const renderStep4 = () => (
        <div className="hph-form-section">
            <div className="hph-form-section-header">
                <Upload className="hph-form-section-icon" />
                <div>
                    <h3>Media & Marketing</h3>
                    <p>Upload photos and add marketing descriptions</p>
                </div>
            </div>

            <div className="hph-form-group">
                <label className="hph-form-label">Property Photos</label>
                <MediaUploadHandler
                    onMediaChange={(media) => handleInputChange('property_images', media)}
                    initialMedia={formData.property_images || []}
                    maxFiles={50}
                    acceptedTypes={['image/*']}
                    maxFileSize={10}
                    categories={['Exterior', 'Living Areas', 'Kitchen', 'Bedrooms', 'Bathrooms', 'Other Spaces']}
                />
            </div>

            <div className="hph-form-group">
                <label className="hph-form-label">Property Description</label>
                <textarea
                    value={formData.property_description}
                    onChange={(e) => handleInputChange('property_description', e.target.value)}
                    className="hph-form-textarea"
                    rows="6"
                    placeholder="Describe the property's best features, location benefits, and unique selling points..."
                />
            </div>

            <div className="hph-form-group">
                <label className="hph-form-label">Agent Remarks (MLS)</label>
                <textarea
                    value={formData.listing_remarks}
                    onChange={(e) => handleInputChange('listing_remarks', e.target.value)}
                    className="hph-form-textarea"
                    rows="4"
                    placeholder="Internal notes for other agents..."
                />
            </div>

            <div className="hph-form-group">
                <label className="hph-form-label">Showing Instructions</label>
                <textarea
                    value={formData.showing_instructions}
                    onChange={(e) => handleInputChange('showing_instructions', e.target.value)}
                    className="hph-form-textarea"
                    rows="3"
                    placeholder="Special instructions for showing this property..."
                />
            </div>
        </div>
    );

    const renderStep5 = () => (
        <div className="hph-form-section">
            <div className="hph-form-section-header">
                <DollarSign className="hph-form-section-icon" />
                <div>
                    <h3>Pricing & Availability</h3>
                    <p>Set pricing and listing status information</p>
                </div>
            </div>

            <div className="hph-form-grid hph-form-grid--2-col">
                <div className="hph-form-group">
                    <label className="hph-form-label">Listing Price *</label>
                    <input
                        type="number"
                        value={formData.price}
                        onChange={(e) => handleInputChange('price', e.target.value)}
                        className={`hph-form-input ${errors.price ? 'error' : ''}`}
                        placeholder="450000"
                        min="0"
                        step="1000"
                    />
                    {errors.price && <span className="hph-form-error">{errors.price}</span>}
                </div>

                <div className="hph-form-group">
                    <label className="hph-form-label">List Date</label>
                    <input
                        type="date"
                        value={formData.list_date}
                        onChange={(e) => handleInputChange('list_date', e.target.value)}
                        className="hph-form-input"
                    />
                </div>

                <div className="hph-form-group">
                    <label className="hph-form-label">MLS Number</label>
                    <input
                        type="text"
                        value={formData.mls_number}
                        onChange={(e) => handleInputChange('mls_number', e.target.value)}
                        className="hph-form-input"
                        placeholder="DESU123456"
                    />
                </div>

                <div className="hph-form-group">
                    <label className="hph-form-label">Listing Status</label>
                    <select
                        value={formData.listing_status}
                        onChange={(e) => handleInputChange('listing_status', e.target.value)}
                        className="hph-form-select"
                    >
                        <option value="draft">Draft</option>
                        <option value="coming_soon">Coming Soon</option>
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="sold">Sold</option>
                        <option value="withdrawn">Withdrawn</option>
                    </select>
                </div>

                <div className="hph-form-group">
                    <label className="hph-form-label">Property Tax (annual)</label>
                    <input
                        type="number"
                        value={formData.property_tax}
                        onChange={(e) => handleInputChange('property_tax', e.target.value)}
                        className="hph-form-input"
                        placeholder="8500"
                        min="0"
                    />
                </div>

                <div className="hph-form-group">
                    <label className="hph-form-label">HOA Fees (monthly)</label>
                    <input
                        type="number"
                        value={formData.hoa_fees}
                        onChange={(e) => handleInputChange('hoa_fees', e.target.value)}
                        className="hph-form-input"
                        placeholder="250"
                        min="0"
                    />
                </div>
            </div>
        </div>
    );

    const renderStep6 = () => (
        <div className="hph-form-section">
            <div className="hph-form-section-header">
                <Eye className="hph-form-section-icon" />
                <div>
                    <h3>Review & Publish</h3>
                    <p>Review your listing information before publishing</p>
                </div>
            </div>

            <div className="hph-listing-preview">
                <div className="hph-preview-section">
                    <h4>Property Summary</h4>
                    <div className="hph-preview-grid">
                        <div className="hph-preview-item">
                            <span className="label">Type:</span>
                            <span className="value">{formData.property_type || 'Not specified'}</span>
                        </div>
                        <div className="hph-preview-item">
                            <span className="label">Address:</span>
                            <span className="value">
                                {`${formData.street_number} ${formData.street_name}`.trim() || 'Not specified'}
                                {formData.unit_number && `, ${formData.unit_number}`}
                                <br />
                                {`${formData.city}, ${formData.state} ${formData.zip_code}`.trim()}
                            </span>
                        </div>
                        <div className="hph-preview-item">
                            <span className="label">Price:</span>
                            <span className="value">${formData.price ? Number(formData.price).toLocaleString() : 'Not specified'}</span>
                        </div>
                        <div className="hph-preview-item">
                            <span className="label">Bedrooms:</span>
                            <span className="value">{formData.bedrooms || 'Not specified'}</span>
                        </div>
                        <div className="hph-preview-item">
                            <span className="label">Bathrooms:</span>
                            <span className="value">{formData.bathrooms || 'Not specified'}</span>
                        </div>
                        <div className="hph-preview-item">
                            <span className="label">Square Footage:</span>
                            <span className="value">{formData.square_footage ? Number(formData.square_footage).toLocaleString() + ' sq ft' : 'Not specified'}</span>
                        </div>
                    </div>
                </div>

                <div className="hph-preview-section">
                    <h4>Features Summary</h4>
                    <div className="hph-feature-summary">
                        {['interior_features', 'exterior_features', 'utility_features'].map(category => {
                            const features = formData[category] || [];
                            return features.length > 0 && (
                                <div key={category} className="hph-feature-group">
                                    <strong>{category.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}:</strong>
                                    <span>{features.join(', ')}</span>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>

            <div className="hph-publish-options">
                <div className="hph-form-actions">
                    <button
                        type="button"
                        onClick={() => handleSubmit('draft')}
                        disabled={isLoading}
                        className="hph-btn hph-btn--secondary"
                    >
                        <Save size={16} />
                        {isLoading ? 'Saving...' : 'Save as Draft'}
                    </button>
                    
                    <button
                        type="button"
                        onClick={() => handleSubmit('publish')}
                        disabled={isLoading}
                        className="hph-btn hph-btn--primary"
                    >
                        <Star size={16} />
                        {isLoading ? 'Publishing...' : 'Publish Listing'}
                    </button>
                </div>
            </div>
        </div>
    );

    const renderCurrentStep = () => {
        switch (currentStep) {
            case 1: return renderStep1();
            case 2: return renderStep2();
            case 3: return renderStep3();
            case 4: return renderStep4();
            case 5: return renderStep5();
            case 6: return renderStep6();
            default: return renderStep1();
        }
    };

    return (
        <div className="hph-multistep-form">
            {/* Form Header */}
            <div className="hph-form-header">
                <div className="hph-form-header-content">
                    <h2>{config.isEditing ? 'Edit Listing' : 'Create New Listing'}</h2>
                    {lastSaved && (
                        <div className="hph-auto-save-indicator">
                            <Save size={14} />
                            <span>Last saved: {lastSaved}</span>
                        </div>
                    )}
                </div>
                
                <div className="hph-progress-bar">
                    <div 
                        className="hph-progress-fill" 
                        style={{ width: `${(currentStep / steps.length) * 100}%` }}
                    />
                </div>
            </div>

            {/* Step Indicator */}
            {renderStepIndicator()}

            {/* Form Content */}
            <div className="hph-form-content">
                {renderCurrentStep()}
            </div>

            {/* Form Navigation */}
            <div className="hph-form-navigation">
                <button
                    type="button"
                    onClick={prevStep}
                    disabled={currentStep === 1}
                    className="hph-btn hph-btn--outline"
                >
                    <ArrowLeft size={16} />
                    Previous
                </button>

                <div className="hph-nav-spacer" />

                {currentStep < steps.length && (
                    <button
                        type="button"
                        onClick={nextStep}
                        className="hph-btn hph-btn--primary"
                    >
                        Next
                        <ArrowRight size={16} />
                    </button>
                )}
            </div>
        </div>
    );
};

export default MultiStepListingForm;
