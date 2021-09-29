const PRODUCTION_FILES_LIST = [
    '**' ,
    '!.git/**',
    '!.sass-cache/**',
    '!_o*/**',
    '!debug/**',
    '!**/sass/**',
    '!bower_components/**',
    '!node_modules/**',
    '!**/test*/**',
    '!.gitattributes',
    '!.gitignore',
    '!Gruntfile.js',
    '!**/*.bat',
    '!**/*.bak',
    '!**/*.bak2',
    '!**/*.yml',
    '!**/*.json',
    '!**/*.lock',
    '!**/c3.php',
    '!**/*.xml',
    '!**/*.neon*',
    '!*.md',
    '!*.zip'
];

module.exports = function(grunt) {
    'use strict';
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        jshint: { // https://github.com/gruntjs/grunt-contrib-jshint
            all: [
                'Gruntfile.js',
                'js/default/*.js',
                'js/bigbutton/*.js',
                'js/smallbutton/*.js',
                'admin/js/src/*.js',
            ]
        },

        uglify: {
            dist: {
                files: {
                    // 'js/cookiechoices.php': [
                    //     'js/cookiechoices.js'
                    // ],
                    'js/default/cookiechoices.php': [
                        'js/default/cookiechoices.js'
                    ],
                    'js/bigbutton/cookiechoices.php': [
                        'js/bigbutton/cookiechoices.js'
                    ],
                    'js/smallbutton/cookiechoices.php': [
                        'js/smallbutton/cookiechoices.js'
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

        compass:{ // https://github.com/gruntjs/grunt-contrib-compass
            src:{
                options: {
                    sassDir:['admin/css/src/sass'],
                    cssDir:['admin/css'],
                    outputStyle: 'compressed'
                }
            },
        },

        // csslint: { // http://astainforth.com/blogs/grunt-part-2
        //     files: ['css/*.css', '!css/bootstrap.min.css',],
        //     options: {
        //         "important": false,
        //         "ids": false,
        //     }
        // },

        gitcheckout: {
            master: {
                options: {
                    branch: 'master'
                }
            },
            dev: {
                options: {
                    branch: 'Dev'
                }
            }
        },

        gitmerge: {
            fromdev: {
                options: {
                    branch: 'Dev'
                }
            },
            frommaster: {
                options: {
                    branch: 'master'
                }
            }
        },

        version: {  // https://www.npmjs.com/package/grunt-version
                    // http://jayj.dk/using-grunt-automate-theme-releases/
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
                        src: PRODUCTION_FILES_LIST, // What should be included in the zip
                        dest: '<%= pkg.name %>/',        // Where the zipfile should go
                        // dest: 'italystrap/',        // Where the zipfile should go
                        filter: 'isFile',
                    },
                ]
            },
            backup: {
                options: {
                    archive: '../<%= pkg.name %> <%= pkg.version %>backup.zip' // Create zip file in theme directory
                },
                files: [
                    {
                        src: PRODUCTION_FILES_LIST, // What should be included in the zip
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

        clean: { // https://github.com/gruntjs/grunt-contrib-clean
            options: {
                force: true,
                // 'no-write': true
            },
            trunk: ['E:/Dropbox/svn-wordpress/italy-cookie-choices/trunk/*']

        },

        copy: { // https://github.com/gruntjs/grunt-contrib-copy
            tosvn: {
                expand: true,
                src: PRODUCTION_FILES_LIST,
                dest: 'E:/Dropbox/svn-wordpress/italy-cookie-choices/trunk/',
                filter: 'isFile',
            },
            totag: {
                expand: true,
                src: PRODUCTION_FILES_LIST,
                dest: 'E:/Dropbox/svn-wordpress/italy-cookie-choices/tags/<%= pkg.version %>/',
                filter: 'isFile',
            },
            /**
             * This task is only for copy files in new project site
             * In "dest" insert the destination of new site that I have to develope
             * Comment this code if I'm developing in this directory
             */
            test: {
                expand: true,
                // cwd: 'src',
                src: PRODUCTION_FILES_LIST,
                dest: 'E:/App/wp-plugin-test/<%= pkg.name %> <%= pkg.version %>/',
                filter: 'isFile',
            },
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
                tasks: ['css'],
            },
            js: {
                files: ['src/js/*.js'],
                tasks: ['script'],
            },
            options: {
                livereload: 9000,
            },
        },

    });

    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    // grunt.loadNpmTasks('grunt-contrib-csslint');
    grunt.loadNpmTasks('grunt-contrib-compass');
    // grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-watch');
    // grunt.loadNpmTasks('grunt-sync');

    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-clean');

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
     * Open Git Bash and type:
     * $ grunt deploy
     * 
     * Poi nella cartella svn-wordpress
     * dx mouse e +add
     * dx mouse e commit
     */
    grunt.registerTask(
        'deploy', [
            'gitcommit:first',
            'gitcheckout:master',
            'gitmerge:fromdev',
            'version',
            'wp_readme_to_markdown',
            'gitcommit:version',
            'gitpush', //
            'prompt',
            'compress:main',
            'github-release',
            'clean',
            'copy',
            'gitcheckout:dev',
            'gitmerge:frommaster',
            'gitpush',
        ]);

    grunt.registerTask(
        'release', [
            'prompt',
            'compress:main',
            'github-release',
    ]);

    grunt.registerTask('readme', ['wp_readme_to_markdown']);

    grunt.registerTask('css', ['csslint', 'compass']);
    grunt.registerTask('script', ['jshint', 'uglify']);

    grunt.registerTask('test', ['jshint', 'csslint']);
    grunt.registerTask('build', ['uglify', 'compass']);

    grunt.event.on('watch', function(action, filepath) {
      grunt.log.writeln(filepath + ' has ' + action);
    });

};