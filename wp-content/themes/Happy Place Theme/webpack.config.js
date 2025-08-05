const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
  mode: 'production',
  entry: {
    main: './assets/src/js/main.js',
    dashboard: './assets/src/js/dashboard-entry.js'
  },
  output: {
    path: path.resolve(__dirname, 'assets/dist'),
    filename: '[name].[contenthash].js',
    clean: true
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
      },
      {
        test: /\.scss$/,
        use: [
          MiniCssExtractPlugin.loader,
          'css-loader',
          'postcss-loader',
          'sass-loader'
        ]
      }
    ]
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: '[name].[contenthash].css'
    }),
    {
      apply(compiler) {
        compiler.hooks.emit.tap('CreateManifest', (compilation) => {
          const manifest = {};
          for (const chunk of compilation.chunks) {
            for (const file of chunk.files) {
              if (file.endsWith('.js')) {
                manifest[`${chunk.name}.js`] = file;
              }
              if (file.endsWith('.css')) {
                manifest[`${chunk.name}.css`] = file;
              }
            }
          }
          
          const manifestJson = JSON.stringify(manifest, null, 2);
          compilation.assets['manifest.json'] = {
            source: () => manifestJson,
            size: () => manifestJson.length
          };
        });
      }
    }
  ]
};