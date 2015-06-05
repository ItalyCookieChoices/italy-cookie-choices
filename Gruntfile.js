module.exports = function(grunt) {
    'use strict';
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        jshint: { // https://github.com/gruntjs/grunt-contrib-jshint
            all: [
                'Gruntfile.js',
                'js/*.js',
            ]
        },

        uglify: {
            dist: {
                files: {
                    'js/cookiechoices.php': [
                        'js/cookiechoices.js'
                    ],                  
                }
            },
            admin: {
                files: {
                    'admin/js/script.min.js': [
                        'admin/js/src/script.js'
                    ]
                }
            }
        },

        // compass:{ // https://github.com/gruntjs/grunt-contrib-compass
        //     src:{
        //         options: {
        //             sassDir:['css/src/sass'],
        //             cssDir:['css'],
        //             outputStyle: 'compressed'
        //         }
        //     },
        // },

        // less: { // https://github.com/gruntjs/grunt-contrib-less
        //     development: {
        //         options: {
        //             compress: true,
        //             yuicompress: true,
        //             optimization: 2
        //         },
        //         files: {
        //             'admin/css/bootstrap.min.css': [
        //                 'admin/css/src/less/bootstrap.less'
        //                 ],
        //           }
        //     }
        // },

        // csslint: { // http://astainforth.com/blogs/grunt-part-2
        //     files: ['css/*.css', '!css/bootstrap.min.css',],
        //     options: {
        //         "important": false,
        //         "ids": false,
        //     }
        // },

        gitcheckout: {
            devtomaster: { // Mi sposto da Dev a master
                options: {
                    branch: 'master'
                }
            },
            mastertodev: { // Mi sposto da master a Dev
                options: {
                    branch: 'Dev'
                }
            }
        },

        gitmerge: {
            fromdev: { // Prima devo essewre in master e poi fare il merge da Dev
                options: {
                    branch: 'Dev'
                }
            },
            frommaster: { // Prima devo essere in dev e poi fare il merge sa master
                options: {
                    branch: 'master'
                }
            }
        },

        version: {  // https://www.npmjs.com/package/grunt-version
                    // http://jayj.dk/using-grunt-automate-theme-releases/
            // bower: {
            //     src: [ 'bower.json' ],
            // },
            php: {
                options: {
                    prefix: 'Version\\:\\s+'
                },
                src: [ 'italy-cookie-choices.php' ],
            },
            readme: {
                options: {
                    prefix: 'Stable tag\\:\\s'
                },
                  src: ['readme.txt']
            },
        },

        wp_readme_to_markdown: { // https://www.npmjs.com/package/grunt-wp-readme-to-markdown
            readme: {
                files: {
                  'README.md': 'readme.txt'
                },
            },
        },

        gitcommit: { // https://www.npmjs.com/package/grunt-git
            version: {
                options: {
                    message: 'New version: <%= pkg.version %>'
                },
                files: {
                    // Specify the files you want to commit
                    src: [
                        // 'bower.json', //For now bower it is not uploaded
                        'readme.txt',
                        'README.md',
                        'package.json',
                        'italy-cookie-choices.php'
                        ]
                }
            },
           first:{
                options: {
                    message: 'Commit before deploy of new version'
                },
                files: {
                    src: [
                        '*.js',
                        '*.txt',
                        '*.php',
                        '*.json'
                        ]
                }
            }
        },

        gitpush: { // https://www.npmjs.com/package/grunt-git
            version: {},
        },

        prompt: { // https://github.com/dylang/grunt-prompt
            target: {
                options: {
                    questions: [
                        {
                        config: 'github-release.options.auth.user', // set the user to whatever is typed for this question
                        type: 'input',
                        message: 'GitHub username:'
                        },
                        {
                        config: 'github-release.options.auth.password', // set the password to whatever is typed for this question
                        type: 'password',
                        message: 'GitHub password:'
                        }
                    ]
                }
            }
        },

        compress: { // https://github.com/gruntjs/grunt-contrib-compress
            main: {
                options: {
                    archive: '../<%= pkg.name %> <%= pkg.version %>.zip' // Create zip file in theme directory
                },
                files: [
                    {
                        src: [
                            '**' ,
                            '!.git/**',
                            '!.sass-cache/**',
                            '!bower_components/**',
                            '!node_modules/**',
                            '!.gitattributes',
                            '!.gitignore',
                            // '!bower.json',
                            // '!Gruntfile.js',
                            // '!package.json',
                            '!*.zip'], // What should be included in the zip
                        dest: '<%= pkg.name %>/',        // Where the zipfile should go
                        // dest: 'italystrap/',        // Where the zipfile should go
                        filter: 'isFile',
                    },
                ]
            }
        },

        "github-release": { // https://github.com/dolbyzerr/grunt-github-releaser
            options: {
                repository: 'ItalyCookieChoices/italy-cookie-choices', // Path to repository
                release: {
                    name: '<%= pkg.name %> <%= pkg.version %>',
                    body: '## New release of <%= pkg.name %> <%= pkg.version %> \nSee the **[changelog](https://github.com/ItalyCookieChoices/italy-cookie-choices#changelog)**',
                }
            },
            files: {
                src: ['../<%= pkg.name %> <%= pkg.version %>.zip'] // Files that you want to attach to Release
            }

        },

        copy: { // https://github.com/gruntjs/grunt-contrib-copy
            tosvn: {
                expand: true,
                src: [
                    '**',
                    '!node_modules/**',
                    '!bower_components/**',
                    '!bower.json',
                    '!composer.json',
                    '!Gruntfile.js',
                    '!package.json',
                    '!README.md',
                    '!composer.lock',
                    '!vendor/mobiledetect/**'
                    ],
                dest: 'E:/Dropbox/svn-wordpress/italy-cookie-choices/trunk/',
                filter: 'isFile',
            },
            totag: {
                expand: true,
                src: [
                    '**',
                    '!node_modules/**',
                    '!bower_components/**',
                    '!bower.json',
                    '!composer.json',
                    '!Gruntfile.js',
                    '!package.json',
                    '!README.md',
                    '!composer.lock',
                    '!vendor/mobiledetect/**'
                    ],
                dest: 'E:/Dropbox/svn-wordpress/italy-cookie-choices/tags/<%= pkg.version %>/',
                filter: 'isFile',
            },
            /**
             * This task is only for copy files in new project site
             * In "dest" insert the destination of new site that I have to develope
             * Comment this code if I'm developing in this directory
             */
            // todev: {
            //     expand: true,
            //     // cwd: 'src',
            //     src: [
            //         '**',
            //         '!node_modules/**',
            //         '!bower_components/**',
            //         '!bower.json',
            //         '!composer.json',
            //         '!Gruntfile.js',
            //         '!package.json',
            //         '!README.md',
            //         ],
            //     dest: 'F:/xampp/htdocs/spadari/wp-content/plugins/italystrap/',
            //     filter: 'isFile',
            // },
        },

        // sync: { // https://www.npmjs.com/package/grunt-sync
        //     main: {
        //         files: [{
        //             cwd: 'src',
        //             src: [
        //                 '**', /* Include everything */
        //                 '!**/*.txt' /* but exclude txt files */
        //                 ],
        //             dest: 'bin',
        //             }],
        //     pretend: true, // Don't do any IO. Before you run the task with `updateAndDelete` PLEASE MAKE SURE it doesn't remove too much.
        //     verbose: true // Display log messages when copying files
        //     }
        // },

        watch: { // https://github.com/gruntjs/grunt-contrib-watch
            css: {
                files: ['**/*.{scss,sass}'],
                tasks: ['testcssbuild'],
            },
            js: {
                files: ['src/js/*.js'],
                tasks: ['testjsbuild'],
            },
            options: {
                livereload: 9000,
            },
        },

    });

    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    // grunt.loadNpmTasks('grunt-contrib-csslint');
    // grunt.loadNpmTasks('grunt-contrib-compass');
    // grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-watch');
    // grunt.loadNpmTasks('grunt-sync');

    grunt.loadNpmTasks('grunt-contrib-copy');

    grunt.loadNpmTasks('grunt-version');
    grunt.loadNpmTasks('grunt-wp-readme-to-markdown');
    grunt.loadNpmTasks('grunt-git');
    grunt.loadNpmTasks('grunt-prompt');
    grunt.loadNpmTasks('grunt-contrib-compress');
    grunt.loadNpmTasks('grunt-github-releaser');

    /**
     * Controllare gli aggiornamenti
     * @link https://www.npmjs.com/package/npm-check-updates
     *
     * Show any new dependencies for the project in the current directory:
     * $ npm-check-updates
     *
     * Upgrade a project's package.json:
     * $ npm-check-updates -u
     *
     * Upgrade
     * $ npm install
     *
     * Controllare gli aggiornamenti con composer (mobile detect per ora la copio a mano in lib)
     * $composer update
     *
     * Copiare dalla cartella composer dentro lib il file di interesse
     * Eventualmente copiarlo anche nel tema
     */

    /**
     * My workflow
     * Update Readme.txt Documentation
     * Add new screanshot
     * Update changelog only in readme.txt
     * Update the documentation in plugin file
     * Update Homepage plugin in admin dashboard (the box functionality)
     *
     * Aggiornare la lingua con poedit
     *
     * Change version only in package.json
     *
     * 
     * $ grunt deploy
     * 
     * Poi nella cartella svn-wordpress
     * dx mouse e +add
     * dx mouse e committ
     */
    grunt.registerTask('deploy', [
                                'gitcommit:first',
                                'gitcheckout:devtomaster',
                                'gitmerge:fromdev',
                                'version',
                                'wp_readme_to_markdown',
                                'gitcommit:version',
                                'gitpush',
                                'prompt',
                                'compress',
                                'github-release',
                                'copy',
                                'gitcheckout:mastertodev',
                                'gitmerge:frommaster',
                                'gitpush',
                                ]);

    grunt.registerTask('release', [
                                'prompt',
                                'compress',
                                'github-release',
                                ]);

    grunt.registerTask('testcssbuild', ['less', 'compass', 'csslint']);
    grunt.registerTask('testjsbuild', ['jshint', 'uglify']);

    // After botstrap update execute "grunt bootstrap"
    grunt.registerTask('bootstrap', ['uglify:bootstrapJS', 'less']);


    grunt.registerTask('test', ['jshint', 'csslint']);
    grunt.registerTask('build', ['uglify', 'less', 'compass']);

    grunt.event.on('watch', function(action, filepath) {
      grunt.log.writeln(filepath + ' has ' + action);
    });

};