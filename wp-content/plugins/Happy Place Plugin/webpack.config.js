const path = require('path');

module.exports = {
    entry: {
        'dashboard': './assets/js/dashboard.js',
        'listing': './assets/js/listing.js',
        'map': './assets/js/map.js',
        'admin': './assets/js/admin.js'
    },
    output: {
        filename: 'js/[name].min.js',
        path: path.resolve(__dirname, 'dist')
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
    plugins: []
};
