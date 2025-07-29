const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const { WebpackManifestPlugin } = require('webpack-manifest-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const { BundleAnalyzerPlugin } = require('webpack-bundle-analyzer');

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';
  const isDevelopment = !isProduction;
  const shouldAnalyze = env && env.analyze;

  return {
    // Entry points - ONLY these two for clean architecture
    entry: {
      main: './assets/src/main.js',     // Frontend entry point
      admin: './assets/src/admin.js',   // Admin/dashboard entry point
    },

    // Output configuration
    output: {
      path: path.resolve(__dirname, 'assets/dist'),
      filename: isProduction ? 'js/[name].[contenthash:8].js' : 'js/[name].js',
      chunkFilename: isProduction ? 'js/[name].[contenthash:8].chunk.js' : 'js/[name].chunk.js',
      clean: true, // Clean dist folder on each build
      publicPath: '/wp-content/themes/Happy Place Theme/assets/dist/',
    },

    // Module resolution
    resolve: {
      extensions: ['.js', '.json'],
      alias: {
        '@': path.resolve(__dirname, 'assets/src/js'),
        '@components': path.resolve(__dirname, 'assets/src/js/components'),
        '@utils': path.resolve(__dirname, 'assets/src/js/utils'),
        '@templates': path.resolve(__dirname, 'assets/src/js/templates'),
        '@scss': path.resolve(__dirname, 'assets/src/scss'),
      },
    },

    // Module rules
    module: {
      rules: [
        // JavaScript/Babel processing
        {
          test: /\.js$/,
          exclude: /node_modules/,
          use: {
            loader: 'babel-loader',
            options: {
              presets: [
                [
                  '@babel/preset-env',
                  {
                    targets: {
                      browsers: ['> 1%', 'last 2 versions', 'not dead', 'not IE 11'],
                    },
                    modules: false, // Let webpack handle modules
                    useBuiltIns: 'usage',
                    corejs: { version: '3.30', proposals: true },
                  },
                ],
              ],
              cacheDirectory: true, // Enable babel caching
            },
          },
        },

        // SCSS/CSS processing
        {
          test: /\.(scss|sass|css)$/,
          use: [
            // Extract CSS in production, use style-loader in development
            isProduction ? MiniCssExtractPlugin.loader : 'style-loader',
            
            // CSS Loader
            {
              loader: 'css-loader',
              options: {
                sourceMap: isDevelopment,
                importLoaders: 2, // Apply postcss-loader and sass-loader to imports
              },
            },
            
            // PostCSS Loader (autoprefixer, cssnano)
            {
              loader: 'postcss-loader',
              options: {
                sourceMap: isDevelopment,
                postcssOptions: {
                  plugins: [
                    ['autoprefixer'],
                    ...(isProduction ? [['cssnano', { preset: 'default' }]] : []),
                  ],
                },
              },
            },
            
            // Sass Loader
            {
              loader: 'sass-loader',
              options: {
                sourceMap: isDevelopment,
                sassOptions: {
                  outputStyle: isProduction ? 'compressed' : 'expanded',
                  includePaths: [path.resolve(__dirname, 'assets/src/scss')],
                },
              },
            },
          ],
        },

        // Image assets
        {
          test: /\.(png|jpe?g|gif|svg|webp|ico)$/i,
          type: 'asset',
          generator: {
            filename: 'images/[name].[hash:8][ext]',
          },
          parser: {
            dataUrlCondition: {
              maxSize: 8 * 1024, // 8kb - inline smaller images
            },
          },
        },

        // Font assets
        {
          test: /\.(woff|woff2|eot|ttf|otf)$/i,
          type: 'asset/resource',
          generator: {
            filename: 'fonts/[name].[hash:8][ext]',
          },
        },

        // Audio/Video assets
        {
          test: /\.(mp3|wav|ogg|mp4|webm)$/i,
          type: 'asset/resource',
          generator: {
            filename: 'media/[name].[hash:8][ext]',
          },
        },
      ],
    },

    // Plugins
    plugins: [
      // Extract CSS into separate files
      new MiniCssExtractPlugin({
        filename: isProduction ? 'css/[name].[contenthash:8].css' : 'css/[name].css',
        chunkFilename: isProduction ? 'css/[name].[contenthash:8].chunk.css' : 'css/[name].chunk.css',
      }),

      // Generate manifest.json for Asset_Manager
      new WebpackManifestPlugin({
        fileName: 'manifest.json',
        publicPath: '',
        writeToFileEmit: true,
        generate: (seed, files, entrypoints) => {
          const manifestFiles = files.reduce((manifest, file) => {
            manifest[file.name] = file.path;
            return manifest;
          }, seed);

          // Add entrypoints for easier Asset_Manager integration
          const entrypointFiles = entrypoints.main.filter(fileName => !fileName.endsWith('.map'));
          manifestFiles['main.js'] = entrypointFiles.find(fileName => fileName.endsWith('.js'));
          manifestFiles['main.css'] = entrypointFiles.find(fileName => fileName.endsWith('.css'));

          if (entrypoints.admin) {
            const adminFiles = entrypoints.admin.filter(fileName => !fileName.endsWith('.map'));
            manifestFiles['admin.js'] = adminFiles.find(fileName => fileName.endsWith('.js'));
            manifestFiles['admin.css'] = adminFiles.find(fileName => fileName.endsWith('.css'));
          }

          return manifestFiles;
        },
      }),

      // Bundle analyzer (only when analyze flag is passed)
      ...(shouldAnalyze ? [new BundleAnalyzerPlugin({ analyzeMode: 'static' })] : []),
    ],

    // Optimization
    optimization: {
      minimize: isProduction,
      minimizer: [
        new TerserPlugin({
          terserOptions: {
            compress: {
              drop_console: isProduction, // Remove console.logs in production
            },
            format: {
              comments: false, // Remove comments
            },
          },
          extractComments: false,
        }),
      ],

      // Code splitting
      splitChunks: {
        chunks: 'all',
        cacheGroups: {
          // Vendor libraries (node_modules)
          vendor: {
            test: /[\\/]node_modules[\\/]/,
            name: 'vendor',
            chunks: 'all',
            priority: 10,
            enforce: true,
          },
          
          // Common code shared between main and admin
          common: {
            name: 'common',
            minChunks: 2,
            chunks: 'all',
            priority: 5,
            enforce: true,
            reuseExistingChunk: true,
          },
          
          // Default group
          default: {
            minChunks: 2,
            priority: -10,
            reuseExistingChunk: true,
          },
        },
      },

      // Runtime chunk for better long-term caching
      runtimeChunk: isProduction ? 'single' : false,
    },

    // Development server configuration
    devServer: {
      static: {
        directory: path.join(__dirname, 'assets/dist'),
      },
      compress: true,
      port: 3000,
      hot: true,
      open: false,
      headers: {
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
        'Access-Control-Allow-Headers': 'X-Requested-With, content-type, Authorization',
      },
      // Proxy API requests to WordPress
      proxy: {
        '/wp-admin': 'http://localhost:8080',
        '/wp-json': 'http://localhost:8080',
      },
    },

    // Source maps
    devtool: isProduction ? 'source-map' : 'eval-cheap-module-source-map',

    // Performance hints
    performance: {
      maxEntrypointSize: 512000, // 500kb
      maxAssetSize: 512000, // 500kb
      hints: isProduction ? 'warning' : false,
    },

    // Stats output configuration
    stats: {
      preset: 'minimal',
      moduleTrace: false,
      errorDetails: true,
      colors: true,
      timings: true,
      assets: true,
      chunks: false,
      modules: false,
      reasons: false,
      children: false,
      source: false,
      warnings: true,
      publicPath: false,
    },

    // Cache configuration for faster rebuilds
    cache: {
      type: 'filesystem',
      cacheDirectory: path.resolve(__dirname, '.webpack-cache'),
      buildDependencies: {
        config: [__filename],
      },
    },

    // Watch options
    watchOptions: {
      ignored: /node_modules/,
      aggregateTimeout: 300,
      poll: false,
    },

    // Target configuration
    target: ['web', 'es2017'],

    // Experiments (optional - for future webpack features)
    experiments: {
      // Enable if you want to use top-level await
      topLevelAwait: false,
    },
  };
};