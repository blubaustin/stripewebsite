const path                 = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
  mode:    'development',
  entry:   {
    app: ['./assets/js/app.js', './assets/css/app.scss']
  },
  output:  {
    path:     path.resolve(__dirname, 'public/build'),
    filename: 'js/[name].js'
  },
  devtool: 'source-map',
  module:  {
    rules: [
      {
        test: /\.js$/,
        loader: 'babel-loader'
      },
      {
        test: /\.scss$/,
        use:  [
          MiniCssExtractPlugin.loader,
          'css-loader',
          'sass-loader'
        ]
      }
    ]
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: 'css/[name].css'
    })
  ]
};
