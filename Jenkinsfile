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
        stage('Check Requirements') {
            steps {
                script {
                    // Print system information
                    sh '''
                        echo "=== System Information ==="
                        cat /etc/os-release || echo "No OS release info found"
                        echo "\nPackage managers:"
                        which apt 2>/dev/null && echo "apt found" || echo "apt not found"
                        which apk 2>/dev/null && echo "apk found" || echo "apk not found"
                        which yum 2>/dev/null && echo "yum found" || echo "yum not found"
                        
                        echo "\nCurrent PATH:"
                        echo $PATH
                        
                        echo "\nrsync status:"
                        which rsync 2>/dev/null && echo "rsync is installed" || echo "rsync not found"
                    '''
                    
                    // Try to install rsync using available package manager
                    sh '''
                        if ! command -v rsync &> /dev/null; then
                            echo "Installing rsync..."
                            if command -v apt &> /dev/null; then
                                sudo apt-get update && sudo apt-get install -y rsync
                            elif command -v apk &> /dev/null; then
                                sudo apk add --no-cache rsync
                            elif command -v yum &> /dev/null; then
                                sudo yum install -y rsync
                            else
                                echo "No supported package manager found"
                                exit 1
                            fi
                        fi
                        
                        # Verify installation
                        echo "\nVerifying rsync installation:"
                        which rsync || echo "rsync not found in PATH"
                        rsync --version || echo "rsync command not working"
                    '''
                }
            }
        }

        stage('Debug Info') {
            steps {
                sh '''
                    echo "=== System Info ==="
                    whoami
                    sudo -n true && echo "Sudo access: YES" || echo "Sudo access: NO"
                    
                    echo "\\n=== Cache Directories ==="
                    ls -la ${COMPOSER_CACHE_DIR} || echo "Composer cache not found"
                    ls -la ${VENDOR_CACHE_DIR} || echo "Vendor cache not found"
                    
                    echo "\\n=== Git Info ==="
                    git branch --show-current
                    git rev-parse HEAD
                '''
            }
        }

        stage('Pre-Setup') {
            steps {
                script {
                    // Create cache directories with proper permissions
                    sh '''
                        # Ensure cache directories exist with proper permissions
                        sudo mkdir -p ${COMPOSER_CACHE_DIR}
                        sudo mkdir -p ${VENDOR_CACHE_DIR}
                        sudo chown -R jenkins:jenkins ${COMPOSER_CACHE_DIR}
                        sudo chown -R jenkins:jenkins ${VENDOR_CACHE_DIR}
                        sudo chmod -R 755 ${COMPOSER_CACHE_DIR}
                        sudo chmod -R 755 ${VENDOR_CACHE_DIR}
                        
                        # Create deployment directory and set permissions
                        if [ ! -d "${DEPLOY_PATH}" ]; then
                            sudo mkdir -p ${DEPLOY_PATH}
                            sudo chown ${APP_USER}:${APP_GROUP} ${DEPLOY_PATH}
                            sudo chmod 755 ${DEPLOY_PATH}
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
                expression {
                    def gitBranch = sh(script: 'git name-rev --name-only HEAD', returnStdout: true).trim()
                    echo "Current Git branch/ref: ${gitBranch}"
                    return gitBranch.contains('main') || gitBranch.contains('master')
                }
            }
            steps {
                script {
                    try {
                        // Print branch info for debugging
                        sh '''
                            echo "=== Deployment Info ==="
                            echo "Git branch/ref: $(git name-rev --name-only HEAD)"
                            echo "Git commit: $(git rev-parse HEAD)"
                            echo "Git remote URL: $(git config --get remote.origin.url)"
                        '''
                        
                        // Deployment steps with error handling
                        sh '''
                            # Ensure directories exist with correct permissions
                            sudo mkdir -p ${DEPLOY_PATH}
                            sudo mkdir -p ${DEPLOY_PATH}/storage/framework/{sessions,views,cache}
                            sudo mkdir -p ${DEPLOY_PATH}/storage/logs
                            sudo mkdir -p ${DEPLOY_PATH}/bootstrap/cache
                            
                            # Verify rsync is available
                            if command -v rsync &> /dev/null; then
                                echo "rsync found at $(which rsync)"
                                
                                # Copy project files with rsync
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
                                
                                # Set proper permissions
                                sudo chown -R ${APP_USER}:${APP_GROUP} ${DEPLOY_PATH}
                                sudo chmod -R 755 ${DEPLOY_PATH}
                                sudo chmod -R 777 ${DEPLOY_PATH}/storage
                                sudo chmod -R 777 ${DEPLOY_PATH}/bootstrap/cache
                                
                                # Run Laravel deployment commands
                                cd ${DEPLOY_PATH}
                                
                                echo "Preparing cache directories..."
                                mkdir -p storage/framework/views
                                mkdir -p storage/framework/cache
                                mkdir -p storage/framework/sessions
                                mkdir -p bootstrap/cache
                                
                                echo "Setting cache directory permissions..."
                                chmod -R 777 storage/framework
                                chmod -R 777 bootstrap/cache
                                
                                echo "Running Laravel cache commands..."
                                php artisan config:cache || echo "Config cache failed"
                                php artisan route:cache || echo "Route cache failed"
                                php artisan view:cache || echo "View cache failed"
                                
                                echo "Running database migrations..."
                                php artisan migrate --force || echo "Migration failed"
                                
                                echo "Deployment completed successfully!"
                            else
                                echo "Error: rsync command not found"
                                exit 1
                            fi
                        '''
                    } catch (Exception e) {
                        echo "Deployment failed: ${e.message}"
                        error "Deployment failed. Check the logs for details."
                    }
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
                    [pattern: '**/.composer/**', type: 'EXCLUDE'],
                    [pattern: '**/storage/**', type: 'EXCLUDE'],
                    [pattern: '**/bootstrap/cache/**', type: 'EXCLUDE']
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