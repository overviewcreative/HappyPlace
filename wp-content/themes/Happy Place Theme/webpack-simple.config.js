const path = require('path');

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';
  
  return {
    entry: {
      main: './assets/src/js/main.js',
      'single-listing': './assets/src/js/single-listing.js',
    },
    
    output: {
      path: path.resolve(__dirname, 'assets/dist/js'),
      filename: isProduction ? '[name].[contenthash].js' : '[name].js',
      clean: true,
    },
    
    module: {
      rules: [
        {
          test: /\.js$/,
          exclude: /node_modules/,
          use: {
            loader: 'babel-loader',
            options: {
              presets: ['@babel/preset-env']
            }
          }
        }
      ]
    },
    
    mode: isProduction ? 'production' : 'development',
    devtool: isProduction ? false : 'source-map',
  };
};
