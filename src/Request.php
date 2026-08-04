<?php
    namespace Naomai\Compactorium;
    class Request {
        protected array $request;
        public static string $method;

        public static function init() : void {
            self::$method = $_SERVER['REQUEST_METHOD'];
        }

        public function __construct(array $requestContent) {
            $this->request = $requestContent;
        }

        public function text(string $key, ?string $default=null) : ?string {
            return $this->validateField($key, is_string(...), $default);

        }

        public function int(string $key, ?int $default=null) : ?int {
            return $this->validateField($key, is_int(...), $default);

        }

        public function bool(string $key, ?bool $default=null) : ?bool {
            return $this->validateField($key, is_bool(...), $default);

        }

        public function array(string $key, ?array $default=null) : ?array {
            return $this->validateField($key, is_array(...), $default);
        }

        protected function validateField(string $key, callable $typeValidationFunction, mixed $default) : mixed {
            if(!isset($this->request[$key]) || !$typeValidationFunction($this->request[$key])) {
                if($default===null) {
                    throw new \Exception("Validation failed for field '{$key}'");
                }
                return $default;
            }
            return $this->request[$key];
        }

        public static function get() : self {
            return new self($_GET);
        }

        public static function post() : self {
            if(self::$method != "POST") {
                throw new \Exception("Invalid request method");
            }

            $contentType = $_SERVER["CONTENT_TYPE"] ?? $_SERVER["HTTP_CONTENT_TYPE"];

            if($contentType=="application/json") {
                $decodedPost = json_decode(file_get_contents('php://input'), true);
                return new self($decodedPost);
            }

            return new self($_POST);
        }



    }