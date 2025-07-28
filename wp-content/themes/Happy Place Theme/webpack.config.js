const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const { WebpackAssetsManifest } = require('webpack-assets-manifest');

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';
  
  return {
    entry: {
      main: ['./assets/src/scss/main.scss', './assets/src/js/main.js'],
      'single-listing': ['./assets/src/scss/single-listing.scss', './assets/src/js/single-listing.js'],
      'single-listing-init': './assets/src/js/single-listing-init.js',
    },
    
    output: {
      path: path.resolve(__dirname, 'assets/dist'),
      filename: isProduction ? 'js/[name].[contenthash].js' : 'js/[name].js',
      clean: true,
    },
    
    module: {
      rules: [
        {
          test: /\.s[ac]ss$/i,
          use: [
            MiniCssExtractPlugin.loader,
            {
              loader: 'css-loader',
              options: {
                sourceMap: !isProduction,
              },
            },
            {
              loader: 'postcss-loader',
              options: {
                postcssOptions: {
                  plugins: [
                    ['autoprefixer'],
                    ...(isProduction ? [['cssnano', { preset: 'default' }]] : []),
                  ],
                },
                sourceMap: !isProduction,
              },
            },
            {
              loader: 'sass-loader',
              options: {
                sourceMap: !isProduction,
                sassOptions: {
                  outputStyle: isProduction ? 'compressed' : 'expanded',
                },
              },
            },
          ],
        },
        {
          test: /\.css$/i,
          use: [
            MiniCssExtractPlugin.loader,
            'css-loader',
            'postcss-loader',
          ],
        },
        {
          test: /\.js$/,
          exclude: /node_modules/,
          use: {
            loader: 'babel-loader',
            options: {
              presets: ['@babel/preset-env'],
            },
          },
        },
      ],
    },
    
    plugins: [
      new MiniCssExtractPlugin({
        filename: isProduction ? 'css/[name].[contenthash].css' : 'css/[name].css',
      }),
      new WebpackAssetsManifest({
        output: 'manifest.json',
        publicPath: '',
        writeToDisk: true,
        transform: (assets) => {
          // Ensure clean paths for WordPress loading
          const cleanAssets = {};
          Object.keys(assets).forEach(key => {
            cleanAssets[key] = assets[key].replace(/^\/+/, '');
          });
          return cleanAssets;
        },
      }),
    ],
    
    optimization: {
      splitChunks: {
        cacheGroups: {
          vendor: {
            test: /[\\/]node_modules[\\/]/,
            name: 'vendors',
            chunks: 'all',
          },
        },
      },
    },
    
    resolve: {
      extensions: ['.js', '.scss', '.css'],
    },
    
    devtool: isProduction ? false : 'source-map',
  };
};
