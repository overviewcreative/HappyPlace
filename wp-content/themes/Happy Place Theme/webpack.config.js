const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const { BundleAnalyzerPlugin } = require('webpack-bundle-analyzer');

module.exports = (env, argv) => {
    const isProduction = argv.mode === 'production';
    const isDevelopment = !isProduction;
    
    return {
        entry: {
            // Main theme scripts
            main: './assets/src/js/main.js',
            
            // Dashboard system
            dashboard: './assets/src/js/dashboard-entry.js',
            
            // Individual pages (using enhanced version)
            'single-listing': './assets/src/js/single-listing-enhanced.js',
            'listing-search': './assets/src/js/listing-search.js',
            
            // Admin scripts
            admin: './assets/src/js/admin.js'
        },
        
        output: {
            path: path.resolve(__dirname, 'assets/dist'),
            filename: 'js/[name].[contenthash].js',
            chunkFilename: 'js/[name].[contenthash].chunk.js',
            publicPath: '/wp-content/themes/Happy Place Theme/assets/dist/',
            clean: true
        },
        
        module: {
            rules: [
                // JavaScript
                {
                    test: /\.js$/,
                    exclude: /node_modules/,
                    use: {
                        loader: 'babel-loader',
                        options: {
                            presets: [
                                ['@babel/preset-env', {
                                    targets: {
                                        browsers: ['defaults', 'not IE 11']
                                    }
                                }]
                            ]
                        }
                    }
                },
                
                // SCSS/CSS
                {
                    test: /\.(scss|css)$/,
                    use: [
                        isProduction ? MiniCssExtractPlugin.loader : 'style-loader',
                        {
                            loader: 'css-loader',
                            options: {
                                importLoaders: 2,
                                sourceMap: isDevelopment
                            }
                        },
                        {
                            loader: 'postcss-loader',
                            options: {
                                postcssOptions: {
                                    plugins: [
                                        ['autoprefixer'],
                                        ...(isProduction ? [['cssnano', { preset: 'default' }]] : [])
                                    ]
                                },
                                sourceMap: isDevelopment
                            }
                        },
                        {
                            loader: 'sass-loader',
                            options: {
                                sourceMap: isDevelopment,
                                sassOptions: {
                                    outputStyle: isProduction ? 'compressed' : 'expanded'
                                }
                            }
                        }
                    ]
                },
                
                // Images
                {
                    test: /\.(png|jpg|jpeg|gif|svg)$/,
                    type: 'asset',
                    generator: {
                        filename: 'images/[name].[hash][ext]'
                    },
                    parser: {
                        dataUrlCondition: {
                            maxSize: 8 * 1024 // 8kb
                        }
                    }
                },
                
                // Fonts
                {
                    test: /\.(woff|woff2|eot|ttf|otf)$/,
                    type: 'asset/resource',
                    generator: {
                        filename: 'fonts/[name].[hash][ext]'
                    }
                }
            ]
        },
        
        plugins: [
            // Extract CSS
            new MiniCssExtractPlugin({
                filename: isProduction ? 'css/[name].[contenthash].css' : 'css/[name].css',
                chunkFilename: isProduction ? 'css/[name].[contenthash].chunk.css' : 'css/[name].chunk.css'
            }),
            
            // Bundle analyzer (only when --analyze flag is passed)
            ...(process.env.ANALYZE ? [
                new BundleAnalyzerPlugin({
                    analyzerMode: 'static',
                    openAnalyzer: false,
                    reportFilename: '../reports/bundle-analysis.html'
                })
            ] : [])
        ],
        
        optimization: {
            minimize: isProduction,
            minimizer: [
                new TerserPlugin({
                    terserOptions: {
                        compress: {
                            drop_console: isProduction,
                            drop_debugger: isProduction
                        },
                        format: {
                            comments: false
                        }
                    },
                    extractComments: false
                })
            ],
            
            splitChunks: {
                chunks: 'all',
                cacheGroups: {
                    vendor: {
                        test: /[\\/]node_modules[\\/]/,
                        name: 'vendors',
                        chunks: 'all',
                        priority: 10
                    },
                    common: {
                        name: 'common',
                        minChunks: 2,
                        chunks: 'all',
                        priority: 5,
                        reuseExistingChunk: true
                    },
                    dashboard: {
                        test: /[\\/]assets[\\/]src[\\/]js[\\/]dashboard[\\/]/,
                        name: 'dashboard-components',
                        chunks: 'all',
                        priority: 8
                    }
                }
            },
            
            runtimeChunk: {
                name: 'runtime'
            }
        },
        
        resolve: {
            extensions: ['.js', '.json'],
            alias: {
                '@': path.resolve(__dirname, 'assets/src'),
                '@js': path.resolve(__dirname, 'assets/src/js'),
                '@scss': path.resolve(__dirname, 'assets/src/scss'),
                '@dashboard': path.resolve(__dirname, 'assets/src/js/dashboard'),
                '@components': path.resolve(__dirname, 'assets/src/js/components'),
                '@utils': path.resolve(__dirname, 'assets/src/js/utils')
            }
        },
        
        externals: {
            jquery: 'jQuery',
            wp: 'wp'
        },
        
        devtool: isDevelopment ? 'eval-source-map' : false,
        
        stats: {
            colors: true,
            modules: false,
            chunks: false,
            chunkModules: false,
            entrypoints: false,
            assets: isProduction
        },
        
        performance: {
            hints: isProduction ? 'warning' : false,
            maxEntrypointSize: 512000,
            maxAssetSize: 512000
        }
    };
};
