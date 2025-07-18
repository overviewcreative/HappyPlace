const path = require('path');

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';
  
  return {
    entry: {
      main: './assets/src/js/main.js',
    },
    
    output: {
      path: path.resolve(__dirname, 'assets/dist'),
      filename: 'js/[name].js',
      clean: true,
    },
    
    module: {
      rules: [
        {
          test: /\.s[ac]ss$/i,
          use: [
            'style-loader',
            'css-loader',
            'sass-loader',
          ],
        },
      ],
    },
    
    resolve: {
      extensions: ['.js', '.scss', '.css'],
    },
    
    devtool: isProduction ? false : 'source-map',
  };
};
