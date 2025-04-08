const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
    ...defaultConfig,
    entry: {
        index: './blocks/animation/index.js',
        transform: './blocks/animation/transform.js'
        // editor.css is automatically handled by @wordpress/scripts
    },
    output: {
        path: path.resolve(__dirname, 'build'),
        filename: '[name].js'
    }
};