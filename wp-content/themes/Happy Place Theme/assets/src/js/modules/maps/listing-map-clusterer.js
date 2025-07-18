/**
 * Happy Place Map Clusterer Configuration
 * 
 * @package HappyPlace
 */

const HPHMapClusterer = {
    /**
     * Create cluster algorithm configuration
     * 
     * @returns {Object} Algorithm configuration
     */
    createAlgorithm() {
        return {
            maxZoom: 15, // Slightly lower max zoom for better clustering
            radius: 75, // Larger cluster radius for better grouping
            
            // Custom distance calculation for better clustering
            distance: (p1, p2) => {
                const rad = Math.PI / 180;
                const lat1 = p1.lat * rad;
                const lat2 = p2.lat * rad;
                const sinDLat = Math.sin((p2.lat - p1.lat) * rad / 2);
                const sinDLon = Math.sin((p2.lng - p1.lng) * rad / 2);
                const a = sinDLat * sinDLat + Math.cos(lat1) * Math.cos(lat2) * sinDLon * sinDLon;
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                return 6371 * c; // Earth's radius in km
            },

            // Add custom clustering options
            minPoints: 2, // Minimum points to form a cluster
            maxPoints: 100, // Maximum points in a single cluster
            
            // Custom cluster bounds calculation
            bounds: (markers) => {
                const bounds = new google.maps.LatLngBounds();
                markers.forEach(marker => bounds.extend(marker.getPosition()));
                return bounds;
            }
        };
    },

    /**
     * Create custom cluster renderer
     * 
     * @returns {Object} Cluster renderer configuration
     */
    createRenderer() {
        return {
            render: ({ count, position }) => {
                // Determine cluster size and style based on count
                let scale, fontSize;
                if (count < 10) {
                    scale = 1.2;
                    fontSize = '14px';
                } else if (count < 50) {
                    scale = 1.5;
                    fontSize = '15px';
                } else if (count < 100) {
                    scale = 1.8;
                    fontSize = '16px';
                } else {
                    scale = 2.2;
                    fontSize = '17px';
                }

                // Create custom cluster marker with improved styling
                const marker = new google.maps.Marker({
                    position,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        fillColor: '#007cba', // Match primary theme color
                        fillOpacity: 0.9,
                        strokeWeight: 2,
                        strokeColor: '#FFFFFF',
                        scale: 22 * scale // Larger base size
                    },
                    label: {
                        text: String(count),
                        color: '#FFFFFF',
                        fontSize: fontSize,
                        fontWeight: '600',
                        className: 'hph-cluster-label'
                    },
                    zIndex: Number(google.maps.Marker.MAX_ZINDEX) + count,
                    cursor: 'pointer'
                });

                // Add hover info window for clusters
                const infoWindow = new google.maps.InfoWindow({
                    content: `
                        <div class="hph-cluster-info">
                            <strong>${count} Properties</strong>
                            <span>Click to zoom in</span>
                        </div>
                    `,
                    disableAutoPan: true,
                    pixelOffset: new google.maps.Size(0, -25 * scale)
                });

                // Add hover listeners
                marker.addListener('mouseover', () => {
                    infoWindow.open(marker.getMap(), marker);
                });

                marker.addListener('mouseout', () => {
                    infoWindow.close();
                });

                return marker;
            }
        };
    },
    
    /**
     * Create cluster algorithm configuration
     * 
     * @returns {Object} Algorithm configuration
     */
    createAlgorithm() {
        return {
            maxZoom: 16, // Maximum zoom level for clustering
            radius: 60, // Cluster radius in pixels
            
            // Custom distance calculation for better clustering
            distance: (p1, p2) => {
                const rad = Math.PI / 180;
                const lat1 = p1.lat * rad;
                const lat2 = p2.lat * rad;
                const sinDLat = Math.sin((p2.lat - p1.lat) * rad / 2);
                const sinDLon = Math.sin((p2.lng - p1.lng) * rad / 2);
                const a = sinDLat * sinDLat + Math.cos(lat1) * Math.cos(lat2) * sinDLon * sinDLon;
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                return 6371 * c; // Earth's radius in km
            }
        };
    }
};
