const path = require('path');

// include the css extraction and minification plugins
const TerserPlugin = require("terser-webpack-plugin");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const OptimizeCSSAssetsPlugin = require("css-minimizer-webpack-plugin");

module.exports = {
    entry: ['./public/assets/js/app.js', './public/assets/sass/app.scss'],
    output: {
        filename: './public/dist/app.min.js',
        assetModuleFilename: "[name][ext]",
        path: path.resolve(__dirname)
    },
    module: {
        rules: [
            // perform js babelization on all .js files
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: "babel-loader",
                    options: {
                        presets: ['@babel/preset-env']
                    }
                }
            },
            // compile all .scss files to plain old css
            {
                test: /\.(sass|scss|css)$/,
                use: [
                    MiniCssExtractPlugin.loader, {
                        // Use the css-loader to parse and minify CSS imports.
                        loader: 'css-loader',
                        options: {
                            sourceMap: true,
                            url: false
                        }
                    },
                    'sass-loader'
                ]
            }
        ]
    },
    plugins: [
        // extract css into dedicated file
        new MiniCssExtractPlugin({
            filename: './public/dist/app.min.css',
        }),
    ],
    optimization: {
        minimize: true,
        minimizer: [
            // enable the js minification plugin
            new TerserPlugin({
                extractComments: false,
                terserOptions: {
                    format: {
                        comments: false,
                    },
                },
            }),
            // enable the css minification plugin
            new OptimizeCSSAssetsPlugin({
                minimizerOptions: {
                    preset: [
                        "default",
                        {
                            discardComments: {
                                removeAll: true
                            },
                        },
                    ],
                },
            })
        ]
    },
    cache: { type: 'filesystem' }
};
