pipeline {
    agent any
    
    environment {
        PHP_VERSION = '8.1'
        COMPOSER_HOME = "${WORKSPACE}/.composer"
        DEPLOY_PATH = '/var/www/laravel-jenkins'
        APP_USER = 'jenkins'
        APP_GROUP = 'jenkins'
        COMPOSER_CACHE_DIR = '/var/jenkins_home/composer_cache'
        VENDOR_CACHE_DIR = '/var/jenkins_home/vendor_cache/laravel'
    }

    triggers {
        pollSCM('* * * * *')  // Poll every minute
    }

    stages {
        stage('Pre-Setup') {
            steps {
                script {
                    // Create cache directories
                    sh '''
                        mkdir -p ${COMPOSER_CACHE_DIR}
                        mkdir -p ${VENDOR_CACHE_DIR}
                        
                        # Create deployment directory and set permissions
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
                sh '''
                    # Use cached dependencies if available
                    if [ -d "${VENDOR_CACHE_DIR}" ]; then
                        echo "Restoring vendor directory from cache..."
                        cp -r ${VENDOR_CACHE_DIR} ./vendor
                    fi
                    
                    # Set Composer cache directory
                    export COMPOSER_CACHE_DIR=${COMPOSER_CACHE_DIR}
                    
                    # Install dependencies
                    composer install --no-interaction --prefer-dist
                    
                    # Cache the vendor directory
                    echo "Caching vendor directory..."
                    rm -rf ${VENDOR_CACHE_DIR}
                    cp -r ./vendor ${VENDOR_CACHE_DIR}
                '''
                
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
            cleanWs(
                deleteDirs: true,
                patterns: [
                    [pattern: '**/node_modules/**', type: 'INCLUDE'],
                    [pattern: '**/vendor/**', type: 'EXCLUDE'],
                    [pattern: '**/.composer/**', type: 'EXCLUDE']
                ]
            )
        }
        success {
            echo '✅ Build successful! All stages completed successfully.'
        }
        failure {
            echo '❌ Build failed! Check the logs for details.'
        }
    }
} 