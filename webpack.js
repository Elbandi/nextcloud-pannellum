const webpackConfig = require('@nextcloud/webpack-vue-config')
const { merge } = require('webpack-merge')

const config = {
	output: {
		filename: 'pannellum-[name].js?v=[contenthash]',
	}
}
const mergedConfig = merge(webpackConfig, config)

module.exports = mergedConfig
