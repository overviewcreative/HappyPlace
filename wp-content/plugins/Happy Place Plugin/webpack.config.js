const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';
  
  return {
    entry: {
      admin: './src/js/admin.js',
      dashboard: './src/js/dashboard.js',
      'marketing-suite': './src/js/marketing-suite-generator.js',
      'field-calculations': './src/js/field-calculations.js',
      integrations: './src/js/integrations.js',
    },
    
    output: {
      path: path.resolve(__dirname, 'dist'),
      filename: 'js/[name].[contenthash:8].js',
      publicPath: '/wp-content/plugins/Happy Place Plugin/dist/',
    },
    
    module: {
      rules: [
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
        {
          test: /\.(scss|css)$/,
          use: [
            MiniCssExtractPlugin.loader,
            'css-loader',
            'postcss-loader',
            'sass-loader',
          ],
        },
        {
          test: /\.(png|jpg|jpeg|gif|svg)$/i,
          type: 'asset/resource',
          generator: {
            filename: 'images/[name].[hash:8][ext]',
          },
        },
      ],
    },
    
    plugins: [
      new CleanWebpackPlugin(),
      new MiniCssExtractPlugin({
        filename: 'css/[name].[contenthash:8].css',
      }),
    ],
    
    optimization: {
      minimize: isProduction,
      minimizer: [
        new TerserPlugin({
          terserOptions: {
            format: {
              comments: false,
            },
          },
          extractComments: false,
        }),
        new CssMinimizerPlugin(),
      ],
      splitChunks: {
        chunks: 'all',
        cacheGroups: {
          vendor: {
            test: /[\\/]node_modules[\\/]/,
            name: 'vendor',
            priority: 10,
          },
        },
      },
    },
    
    devtool: isProduction ? false : 'source-map',
    
    watch: !isProduction,
    watchOptions: {
      ignored: /node_modules/,
    },
  };
};