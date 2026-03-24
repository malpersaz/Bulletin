module.exports = function (grunt) {

    grunt.initConfig({


        // paths
        paths: {
            bower: {
                bower_dir: './bower_components/',
                jquery: './bower_components/jquery/dist/jquery.js',
                modernizr: './bower_components/modernizr/modernizr.js',
                bootstrap_js_dir: './bower_components/bootstrap/js/'
                //bootstrap_less_dir: './bower_components/bootstrap-less/less/',
            },
            out: {
                js_dir: './js/',
                vendor_js: './js/vendor.js',

                css_build_dir: './build/css/',
                css_dir: './css/',
                less_dir: './less/',


                css: './css/main.css'

            }
        },

        // running `grunt less` will compile once
        less: {
            development: {
                options: {
                    paths: ["<%= paths.out.css_build_dir %>"],
                    //yuicompress: true,
                    //cleancss: true,
                    //compress: true,
                    strictMath: true
                },
                files: {
                    "<%= paths.out.css_build_dir %>main.css": "<%= paths.out.less_dir %>main.less"
                }
            }
        },
        // running `grunt watch` will watch for changes
        watch: {
            script: {
                files: ['./js/main.js'],
                tasks: ['uglify'],
                options: {
                    livereload: true
                }
            },
            less: {
                files: ['<%= paths.out.less_dir %>*/*', '<%= paths.out.less_dir %>*'],
                tasks: ["less"],
                options: {
                    livereload: true
                }
            },
            /*lessblock: {
             files: ['<%= paths.out.less_dir %>block/*','<%= paths.out.less_dir %>headers/*','<%= paths.out.less_dir %>*'],
             tasks: ["less"],
             options: {
             livereload: true
             }
             },*/
            autoprefixer: {
                files: "<%= paths.out.css_build_dir %>*.css",
                tasks: ["autoprefixer"],
                options: {
                    livereload: true
                }
            }

        },

        uglify: {

            vendor: {
                options: {
                    beautify: true,
                    livereload: true

                },
                files: {
                    '<%= paths.out.vendor_js %>': [
                        '<%= paths.bower.jquery %>',
                        '<%= paths.bower.modernizr %>',
                        '<%= paths.bower.bootstrap_js_dir %>affix.js',
                        '<%= paths.bower.bootstrap_js_dir %>alert.js',
                        '<%= paths.bower.bootstrap_js_dir %>button.js',
                        '<%= paths.bower.bootstrap_js_dir %>carousel.js',
                        '<%= paths.bower.bootstrap_js_dir %>collapse.js',
                        '<%= paths.bower.bootstrap_js_dir %>dropdown.js',
                        '<%= paths.bower.bootstrap_js_dir %>modal.js',
                        '<%= paths.bower.bootstrap_js_dir %>tooltip.js',
                        '<%= paths.bower.bootstrap_js_dir %>popover.js',
                        '<%= paths.bower.bootstrap_js_dir %>scrollspy.js',
                        '<%= paths.bower.bootstrap_js_dir %>tab.js',
                        '<%= paths.bower.bootstrap_js_dir %>transition.js',
                        '<%= paths.bower.bower_dir %>bootstrap-hover-dropdown/bootstrap-hover-dropdown.js'
                    ]
                }
            }


        },

        autoprefixer: {
            options: {
                // browsers: ['last 2 versions', 'ie 8', 'ie 9']
            },

            // prefix all files
            multiple_files: {
                expand: true,
                flatten: true,
                src: '<%= paths.out.css_build_dir %>*.css', // -> src/css/file1.css, src/css/file2.css
                dest: '<%= paths.out.css_dir %>' // -> dest/css/file1.css, dest/css/file2.css
            }
        },

        // Remove built directory
        clean: {
            build: ['build/']
        },
        // Build the site using grunt-includes
        includes: {
            build: {
                cwd: 'site',
                src: ['*.html', 'headers/*.html', 'footers/*.html', 'blocks/*.html', 'sliders/*.html'],
                dest: 'build/',
                options: {
                    flatten: true,
                    includePath: 'include',
                    banner: '<!-- Site built using grunt includes! -->\n'
                }
            }
        }


    });


    // Plugin loading
    //grunt.loadNpmTasks('grunt-includes');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-autoprefixer');


    // Task definition
    grunt.registerTask('default', [
        'watch'
    ]);
};
