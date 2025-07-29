const path = require('path');

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';
  
  return {
    entry: {
      main: './assets/src/js/main.js',
      'single-listing': './assets/src/js/single-listing.js',
      'dashboard-entry': './assets/src/js/dashboard-entry.js'
    },
    
    output: {
      path: path.resolve(__dirname, 'assets/dist/js'),
      filename: isProduction ? '[name].[contenthash:8].js' : '[name].js',
      clean: true,
    },
    
    mode: isProduction ? 'production' : 'development',
    devtool: isProduction ? false : 'source-map',
    
    // No loaders needed for simple bundling
    resolve: {
      extensions: ['.js']
    }
  };
};
