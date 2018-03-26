module.exports = function(grunt) {

    var es2015Preset = require('babel-preset-es2015');
    var reactPreset = require('babel-preset-react');

    grunt.initConfig( {
        browserify: {
            components: {
                options: {
                    transform: [
                        [ 'babelify', { presets: [ es2015Preset, reactPreset ] } ]
                    ],
                    browserifyOptions: {
                        paths: [ __dirname + '/node_modules' ]
                    }
                },
                src: [
                    'static/src/js/actions/*.js',
                    'static/src/js/dispatcher/*.js',
                    'static/src/js/store/*.js',
                    'static/src/js/components/*.js',
                    'static/src/js/constants.js',
                    'static/src/js/cat_source/paypalUtils.js'
                ],
                dest:  'static/build/paypal-components-build.js'
            },
            preview: {
                options: {
                    transform: [
                        [ 'babelify', { presets: [ es2015Preset, reactPreset ] } ]
                    ],
                    browserifyOptions: {
                        paths: [ __dirname + '/node_modules' ]
                    }
                },
                src: [
                    'static/src/js/paypal-preview.js',
                ],
                dest:  'static/build/paypal-preview-build.js'
            },
            lqa: {
                options: {
                    transform: [
                        [ 'babelify', { presets: [ es2015Preset, reactPreset ] } ]
                    ],
                    browserifyOptions: {
                        paths: [ __dirname + '/node_modules' ]
                    }
                },
                src: [
                    'static/src/js/cat_source/paypal-lqa.js',
                ],
                dest:  'static/build/paypal-lqa-build.js'
            },
            manage: {
                options: {
                    transform: [
                        [ 'babelify', { presets: [ es2015Preset, reactPreset ] } ]
                    ],
                    browserifyOptions: {
                        paths: [ __dirname + '/node_modules' ]
                    }
                },
                src: [
                    'static/src/js/cat_source/paypal-manage.js',
                ],
                dest:  'static/build/paypal-manage-build.js'
            },
            analyze: {
                options: {
                    transform: [
                        [ 'babelify', { presets: [ es2015Preset, reactPreset ] } ]
                    ],
                    browserifyOptions: {
                        paths: [ __dirname + '/node_modules' ]
                    }
                },
                src: [
                    'static/src/js/cat_source/paypal-analyze.js',
                ],
                dest:  'static/build/paypal-analyze-build.js'
            },
            core: {
                options: {
                    transform: [
                        [ 'babelify', { presets: [ es2015Preset, reactPreset ] } ]
                    ],
                    browserifyOptions: {
                        paths: [ __dirname + '/node_modules' ]
                    }
                },
                src: [
                    'static/src/js/cat_source/paypal-core.js',
                    'static/src/js/cat_source/paypal-core.*.js'
                ],
                dest:  'static/build/paypal-core-build.js'
            },
        },
        sass: {
            dist: {
                options: {
                    sourceMap: false,
                    includePaths: ['static/src/css/sass/']
                },
                src: [
                    'static/src/css/sass/paypal.scss'
                ],
                dest: 'static/build/paypal-build.css'
            },
            distManage: {
                options: {
                    sourceMap: false,
                    includePaths: ['static/src/css/sass/']
                },
                src: [
                    'static/src/css/sass/paypal-manage.scss'
                ],
                dest: 'static/build/paypal-manage-build.css'
            },
            distCore: {
                options: {
                    sourceMap: false,
                    includePaths: ['static/src/css/sass/']
                },
                src: [
                    'static/src/css/sass/paypal-core.scss'
                ],
                dest: 'static/build/paypal-core-build.css'
            },
        },
        replace: {
            css: {
                src: [
                    'static/build/*'
                ],
                dest: 'static/build/',
                replacements: [
                    {
                        from: 'url(../img',
                        to: 'url(../src/css/img'
                    }
                ]
            }
        }

    });

    grunt.loadNpmTasks('grunt-browserify');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-sass');
    grunt.loadNpmTasks('grunt-text-replace');

    // Define your tasks here
    grunt.registerTask('default', ['bundle:js']);

    grunt.registerTask('bundle:js', [
        'browserify:components',
        'browserify:preview',
        'browserify:lqa',
        'browserify:manage',
        'browserify:analyze',
        'browserify:core',
        'sass',
        'replace'
    ]);



};
