const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config')
const { merge } = require('webpack-merge')

const config = {
	entry: {
		public: path.resolve(path.join('src', 'public.js')),
	},
	output: {
		filename: 'pannellum-[name].js?v=[contenthash]',
	}
}
const mergedConfig = merge(webpackConfig, config)

module.exports = mergedConfig
