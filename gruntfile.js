module.exports = function(grunt) {
    require('load-grunt-tasks')(grunt);

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        resourcesPath: 'src/AppBundle/Resources',
        sass: {
            bootstrap: {
                files: {
                    'public/css/main.css': 'public/scss/main.scss'
                }
            }
        },
        watch: {
            css: {
                files: 'public/scss/*.scss',
                tasks: ['sass']
            }
        }
    });

    // Load the plugins
    grunt.loadNpmTasks('grunt-contrib-watch');

    // Default task(s).
    grunt.registerTask('default', ['sass']);
};
