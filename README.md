Lilit task
========================

Installation
------------
1. Clone the repository:
    ```sh
    git clone https://github.com/yourusername/yourproject.git
    cd lilit_task
    ```
2. Create Your .env.local File and add Api key

 ```sh
   X_RAPID_API_KEY=your_api_key
```
3. Build and start the services:
    ```sh
    docker-compose up --build
    ```
4. Access the application at `http://localhost:8080`.
   
API Documentation
-----

http://localhost:8080/api/doc

Tests
-----
Execute this command to run tests:

```bash
$ cd lilit_task
$ ./vendor/bin/phpunit
```
