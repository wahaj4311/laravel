pipeline {
    agent any
    
    environment {
        PHP_VERSION = '8.1'
        COMPOSER_HOME = "${WORKSPACE}/.composer"
        DEPLOY_PATH = '/var/www/laravel-jenkins'
        APP_USER = 'jenkins'
        APP_GROUP = 'jenkins'
    }

    triggers {
        pollSCM('*/5 * * * *')  // Poll every 5 minutes
    }

    stages {
        stage('Pre-Setup') {
            steps {
                script {
                    // Create deployment directory and set initial permissions
                    sh '''
                        if [ ! -d "${DEPLOY_PATH}" ]; then
                            mkdir -p ${DEPLOY_PATH}
                            chown ${APP_USER}:${APP_GROUP} ${DEPLOY_PATH}
                            chmod 755 ${DEPLOY_PATH}
                        fi
                    '''
                }
            }
        }

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
                script {
                    // Ensure deployment directory exists and has correct permissions
                    sh '''
                        # Ensure directories exist with correct permissions
                        mkdir -p ${DEPLOY_PATH}
                        mkdir -p ${DEPLOY_PATH}/storage/framework/{sessions,views,cache}
                        mkdir -p ${DEPLOY_PATH}/storage/logs
                        mkdir -p ${DEPLOY_PATH}/bootstrap/cache
                        
                        # Copy project files
                        rsync -av --delete \
                            --exclude='.git' \
                            --exclude='.env' \
                            --exclude='storage' \
                            --exclude='bootstrap/cache' \
                            ./ ${DEPLOY_PATH}/
                        
                        # Copy .env file if it doesn't exist
                        if [ ! -f "${DEPLOY_PATH}/.env" ]; then
                            cp .env ${DEPLOY_PATH}/.env
                        fi
                        
                        # Set proper permissions
                        chown -R ${APP_USER}:${APP_GROUP} ${DEPLOY_PATH}
                        chmod -R 755 ${DEPLOY_PATH}
                        chmod -R 777 ${DEPLOY_PATH}/storage
                        chmod -R 777 ${DEPLOY_PATH}/bootstrap/cache
                        
                        # Run Laravel deployment commands
                        cd ${DEPLOY_PATH}
                        php artisan config:cache
                        php artisan route:cache
                        php artisan view:cache
                        php artisan migrate --force
                        
                        echo "Deployment completed successfully!"
                    '''
                }
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