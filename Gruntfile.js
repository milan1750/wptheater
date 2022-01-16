module.exports = function ( grunt ) {
	'use strict';

	grunt.initConfig( {
		// Setting folder templates.
		dirs: {
			js: 'assets/js',
			css: 'assets/css',
		},

		// JavaScript linting with ESLint.
		eslint: {
			src: [ '<%= dirs.js %>/*.js', '!<%= dirs.js %>/*.min.js' ],
		},

		// Sass linting with Stylelint.
		stylelint: {
			options: {
				configFile: '.stylelintrc.json',
			},
			all: [ '<%= dirs.css %>/*.scss' ],
		},

		// Minify .js files.
		uglify: {
			options: {
				ie8: true,
				parse: {
					strict: false,
				},
				output: {
					comments: /@license|@preserve|^!/,
				},
			},
			js_assets: {
				files: [
					{
						expand: true,
						cwd: '<%= dirs.js %>/',
						src: [ '*.js', '!*.min.js' ],
						extDot: 'last',
						dest: '<%= dirs.js %>',
						ext: '.min.js',
					},
				],
			},
		},

		// Compile all .scss files.
		sass: {
			options: {
				sourceMap: true,
				implementation: require( 'node-sass' ),
			},
			compile: {
				files: [
					{
						expand: true,
						cwd: '<%= dirs.css %>/',
						src: [ '*.scss' ],
						dest: '<%= dirs.css %>/',
						ext: '.css',
					},
				],
			},
		},

		// PostCSS.
		postcss: {
			options: {
				processors: [
					require( 'autoprefixer' )( { grid: true } ),
					require( 'cssnano' )( {
						preset: [
							'default',
							{
								discardComments: {
									removeAll: true,
								},
							},
						],
					} ),
				],
			},
			dist: {
				src: [ '<%= dirs.css %>/*.css' ],
			},
		},

		// Watch changes for assets.
		watch: {
			css: {
				files: [ '<%= dirs.css %>/*.scss' ],
				tasks: [ 'stylelint', 'sass' ],
			},
			js: {
				files: [
					'GruntFile.js',
					'<%= dirs.js %>*.js',
					'!<%= dirs.js %>*.min.js',
				],
				tasks: [ 'eslint', 'newer:uglify' ],
			},
		},
	} );

	// Load NPM tasks to be used here.
	grunt.loadNpmTasks( 'grunt-sass' );
	grunt.loadNpmTasks( 'grunt-newer' );
	grunt.loadNpmTasks( 'grunt-eslint' );
	grunt.loadNpmTasks( 'grunt-stylelint' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( '@lodder/grunt-postcss' );

	// Register tasks
	grunt.registerTask( 'default', [ 'js', 'css' ] );

	grunt.registerTask( 'js', [ 'eslint', 'uglify:js_assets' ] );

	grunt.registerTask( 'css', [ 'stylelint', 'sass', 'postcss' ] );

	// Only an alias to 'default' task.
	grunt.registerTask( 'assets', [ 'default' ] );
};
