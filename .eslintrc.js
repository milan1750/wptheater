const eslintConfig = {
	root: true,
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended' ],
	parserOptions: {
		ecmaVersion: 8,
		ecmaFeatures: {
			modules: true,
			experimentalObjectRestSpread: true,
			jsx: true,
		},
	},
	globals: {
		jQuery: true,
	},
	rules: {
		'no-var': 'off',
		camelcase: 'off',
		'object-shorthand': 'off',
	},
};

module.exports = eslintConfig;
