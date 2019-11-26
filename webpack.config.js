var path = require('path');
var webpack = require('webpack');

var outputPath = path.join(__dirname, 'js/build');

var externals = {};
var fs = require('fs');
var items = fs.readdirSync(path.join(__dirname, '../../components'));
for (var i=0; i<items.length; i++) {
    var name = items[i];
    name = name.substr(0, name.length-3);
    externals[name] = { amd: name };
}

externals['./tags.libs.js'] = { amd: '/intranet/thankyou/js/built/tags.libs.js' };
externals['jquery'] = { amd: 'jquery' };
externals['moment'] = { amd: 'moment' };

var tags = {
    context: __dirname,
    entry: {
        'tags': [
            './js/src/tags.js'
        ],
        'thankyou': [
            './js/src/thankyou.js'
        ]
    },
    output: {
        path: outputPath,
        filename: '[name].bundle.js',
        publicPath: '/intranet/thankyou/js/build/',
        libraryTarget: 'amd'
    },
    resolve: {
        modules: [path.join(__dirname, 'js'), "node_modules"]
    },
    externals: externals,
    module: {
        loaders: [
            { test: /\.html$/, loader: "ngtemplate-loader?relativeTo=" + __dirname + "!html-loader" },
            { test: /\.css$/, loader: "style-loader!css-loader" },
            { test: /\.(png|jpg|woff|woff2|eot|ttf|otf|gif)/, loader: 'url-loader' },
            { test: /\.svg/, loader: 'file?name=/img/[hash].[ext]?' },
            { test: /\.s[a|c]ss/, loader: "style-loader!css-loader!sass-loader" }
        ]
    },
    plugins: [
        new webpack.NamedModulesPlugin(),
        new webpack.optimize.UglifyJsPlugin({
            compress: {
                warnings: false,
                drop_console: false,
                drop_debugger: false
            }
        })
    ]
};

module.exports = [
    tags
];
