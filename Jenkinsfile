pipeline {
    agent any
    
    environment {
        PHP_VERSION = '8.1'
        COMPOSER_HOME = "${WORKSPACE}/.composer"
        DEPLOY_PATH = '/var/www/laravel-jenkins'
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
                sh '''
                    # Create deployment directory if it doesn't exist
                    sudo mkdir -p ${DEPLOY_PATH}
                    
                    # Copy project files
                    sudo rsync -av --delete \
                        --exclude='.git' \
                        --exclude='.env' \
                        --exclude='storage' \
                        --exclude='bootstrap/cache' \
                        ./ ${DEPLOY_PATH}/
                    
                    # Copy .env file if it doesn't exist
                    if [ ! -f "${DEPLOY_PATH}/.env" ]; then
                        sudo cp .env ${DEPLOY_PATH}/.env
                    fi
                    
                    # Create necessary directories
                    sudo mkdir -p ${DEPLOY_PATH}/storage/framework/{sessions,views,cache}
                    sudo mkdir -p ${DEPLOY_PATH}/storage/logs
                    sudo mkdir -p ${DEPLOY_PATH}/bootstrap/cache
                    
                    # Set proper permissions
                    sudo chown -R www-data:www-data ${DEPLOY_PATH}
                    sudo chmod -R 755 ${DEPLOY_PATH}
                    sudo chmod -R 777 ${DEPLOY_PATH}/storage
                    sudo chmod -R 777 ${DEPLOY_PATH}/bootstrap/cache
                    
                    # Run Laravel deployment commands
                    cd ${DEPLOY_PATH}
                    sudo -u www-data php artisan config:cache
                    sudo -u www-data php artisan route:cache
                    sudo -u www-data php artisan view:cache
                    sudo -u www-data php artisan migrate --force
                    
                    # Restart PHP-FPM (if using)
                    # sudo systemctl restart php8.1-fpm
                    
                    echo "Deployment completed successfully!"
                '''
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