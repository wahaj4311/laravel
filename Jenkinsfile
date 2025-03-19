pipeline {
    agent any
    
    environment {
        PHP_VERSION = '8.1'
        COMPOSER_HOME = "${WORKSPACE}/.composer"
    }

    stages {
        stage('Setup') {
            steps {
                sh 'php -v'
                sh 'composer --version'
            }
        }
        
        stage('Install Dependencies') {
            steps {
                sh 'composer install --no-interaction --prefer-dist'
                sh 'cp .env.example .env'
                sh '''
                    mkdir -p database
                    touch database/database.sqlite
                    chmod -R 777 database
                    chmod -R 777 storage
                    chmod -R 777 bootstrap/cache
                '''
                sh 'php artisan key:generate'
                sh 'php artisan migrate:fresh --force'
            }
        }
        
        stage('Code Analysis') {
            steps {
                sh './vendor/bin/phpunit --version'
                sh './vendor/bin/phpstan analyse app tests --level=0 || true'
            }
        }
        
        stage('Tests') {
            steps {
                sh 'php artisan test'
            }
        }
        
        stage('Build') {
            steps {
                sh '''
                    php artisan config:clear
                    php artisan cache:clear
                    php artisan route:clear
                    php artisan view:clear
                '''
            }
        }
        
        stage('Deploy') {
            when {
                branch 'main'
            }
            steps {
                echo 'Deploying application...'
                // Add your deployment steps here
                // Example: sh 'rsync -av --delete ./ /var/www/production/'
            }
        }
    }
    
    post {
        always {
            cleanWs()
        }
        success {
            echo 'Build successful!'
        }
        failure {
            echo 'Build failed!'
        }
    }
} 