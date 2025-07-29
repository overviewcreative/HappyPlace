const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const HtmlWebpackPlugin = require('html-webpack-plugin');

module.exports = (env, argv) => {
    const isProduction = argv.mode === 'production';
    
    return {
        // Entry points for different page types
        entry: {
            dashboard: './wp-content/themes/Happy Place Theme/assets/src/js/dashboard-entry.js',
            main: './wp-content/themes/Happy Place Theme/assets/src/js/main.js',
            'single-listing': './wp-content/themes/Happy Place Theme/assets/src/js/single-listing.js'
        },
        
        // Output configuration
        output: {
            path: path.resolve(__dirname, 'wp-content/themes/Happy Place Theme/assets/dist'),
            filename: isProduction ? 'js/[name].[contenthash].js' : 'js/[name].js',
            chunkFilename: isProduction ? 'js/[name].[contenthash].chunk.js' : 'js/[name].chunk.js',
            clean: true,
            publicPath: '/wp-content/themes/Happy Place Theme/assets/dist/'
        },
        
        // Module resolution
        resolve: {
            extensions: ['.js', '.jsx', '.ts', '.tsx', '.json'],
            alias: {
                '@': path.resolve(__dirname, 'wp-content/themes/Happy Place Theme/assets/src/js'),
                '@dashboard': path.resolve(__dirname, 'wp-content/themes/Happy Place Theme/assets/src/js/dashboard'),
                '@components': path.resolve(__dirname, 'wp-content/themes/Happy Place Theme/assets/src/js/components'),
                '@integrations': path.resolve(__dirname, 'wp-content/themes/Happy Place Theme/assets/src/js/integrations'),
                '@utils': path.resolve(__dirname, 'wp-content/themes/Happy Place Theme/assets/src/js/utils')
            }
        },
        
        // Module rules
        module: {
            rules: [
                // JavaScript/ES6+ handling
                {
                    test: /\.js$/,
                    exclude: /node_modules/,
                    use: {
                        loader: 'babel-loader',
                        options: {
                            presets: [
                                ['@babel/preset-env', {
                                    targets: {
                                        browsers: ['> 1%', 'last 2 versions']
                                    },
                                    modules: false
                                }]
                            ]
                        }
                    }
                },
                
                // SCSS/CSS handling
                {
                    test: /\.(scss|sass|css)$/,
                    use: [
                        isProduction ? MiniCssExtractPlugin.loader : 'style-loader',
                        {
                            loader: 'css-loader',
                            options: {
                                sourceMap: !isProduction
                            }
                        },
                        {
                            loader: 'sass-loader',
                            options: {
                                sourceMap: !isProduction,
                                sassOptions: {
                                    includePaths: [
                                        path.resolve(__dirname, 'wp-content/themes/Happy Place Theme/assets/src/scss')
                                    ]
                                }
                            }
                        }
                    ]
                },
                
                // Asset handling
                {
                    test: /\.(png|jpe?g|gif|svg|ico)$/i,
                    type: 'asset/resource',
                    generator: {
                        filename: 'images/[name].[hash][ext]'
                    }
                },
                
                {
                    test: /\.(woff|woff2|eot|ttf|otf)$/i,
                    type: 'asset/resource',
                    generator: {
                        filename: 'fonts/[name].[hash][ext]'
                    }
                },
                
                {
                    test: /\.(mp3|wav|ogg)$/i,
                    type: 'asset/resource',
                    generator: {
                        filename: 'sounds/[name].[hash][ext]'
                    }
                }
            ]
        },
        
        // Plugins
        plugins: [
            // Extract CSS into separate files
            new MiniCssExtractPlugin({
                filename: isProduction ? 'css/[name].[contenthash].css' : 'css/[name].css',
                chunkFilename: isProduction ? 'css/[name].[contenthash].chunk.css' : 'css/[name].chunk.css'
            })
        ],
        
        // Optimization
        optimization: {
            splitChunks: {
                chunks: 'all',
                cacheGroups: {
                    // Vendor libraries
                    vendor: {
                        test: /[\\/]node_modules[\\/]/,
                        name: 'vendor',
                        chunks: 'all',
                        priority: 10
                    },
                    
                    // Common dashboard code
                    dashboard: {
                        test: /[\\/]assets[\\/]src[\\/]js[\\/](components|integrations|dashboard)[\\/]/,
                        name: 'dashboard-common',
                        chunks: 'all',
                        minChunks: 2,
                        priority: 5
                    },
                    
                    // Default chunk
                    default: {
                        minChunks: 2,
                        priority: -10,
                        reuseExistingChunk: true
                    }
                }
            },
            
            // Runtime chunk
            runtimeChunk: {
                name: 'runtime'
            }
        },
        
        // Development server
        devServer: {
            static: {
                directory: path.join(__dirname, 'wp-content/themes/Happy Place Theme/assets/dist'),
            },
            port: 3000,
            hot: true,
            open: false,
            headers: {
                'Access-Control-Allow-Origin': '*'
            }
        },
        
        // Source maps
        devtool: isProduction ? 'source-map' : 'eval-source-map',
        
        // Performance hints
        performance: {
            maxEntrypointSize: 512000,
            maxAssetSize: 512000,
            hints: isProduction ? 'warning' : false
        },
        
        // Stats output
        stats: {
            children: false,
            chunks: false,
            modules: false,
            colors: true,
            errors: true,
            warnings: true
        }
    };
};
