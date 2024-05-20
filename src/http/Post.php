<?php
namespace GemLibrary\Http;
use GemLibrary\Helper\JsonHelper;
class Post{
    public null|string $error;

    /**
     * @param string|array<mixed> $data
     */
    public function __construct(string|array $data)
    {
        $this->error = null;
        if(!is_array($data)){

        }
        else{
            $this->convertIncomingArray($data);
        }
    }

    public function __set(string $key , mixed $value):void
    {
        $this->$key = $value;
    }


    /**
     * @param  array<string> $toValidatePost Define Post Schema to validation
     * @return bool
     * validatePosts(['email'=>'email' , 'id'=>'int' , '?name' => 'string'])
     * @help   : ?name means it is optional
     * @in     case of false $this->error will be set
     */
    public function definePostSchema(array $toValidatePost): bool
    {
        $errors = []; // Initialize an empty array to store errors
        $requires = [];
        $optionals = [];
        $all=[];
        foreach ($toValidatePost as $validation_key => $validationString) {
            if(substr($validation_key, 0, 1) === '?') {
                $validation_key = ltrim($validation_key, '?');
                $optionals[$validation_key] = $validationString;
            }
            else
            {
                $requires[$validation_key] = $validationString;
            }
            $all[$validation_key] = $validationString;
        }
        $properties = get_object_vars($this);
        foreach($properties  as $postName => $postValue) { 
            if(!array_key_exists($postName, $all)  ) {  
                $errors[$postName] = "unwanted post $postName";
                $this->post = []; 
            }
        }
        if (count($errors) > 0) { //if unwanted post exists , stop process and return false
            foreach ($errors as $error) {
                $this->error .= $error . ', '; // Combine errors into a single string
            }
            return false;
        }

        foreach($requires as $validation_key => $validation_value) {      //now only check existence of requires post 
            if ((!isset($this->post[$validation_key]) || empty($this->post[$validation_key]))) {
                $errors[] = "Missing required field: $validation_key";
            }
        }
        if (count($errors) > 0) { //if requires not exists , stop process and return false
            foreach ($errors as $error) {
                $this->error .= $error . ', '; // Combine errors into a single string
            }
            return false;
        }

        foreach($requires as $validation_key => $validationString) { //now validate requires post Schema
            $validationResult = $this->checkPostKeyValue($validation_key, $validationString);
            if (!$validationResult) {
                $errors[] = "Invalid value for field: $validation_key";
            }
        }
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->error .= $error . ', '; // Combine errors into a single string
            }
            return false;
        }

        foreach($optionals as $optionals_key => $optionals_value) { //check optionals if post exists and not null then do check
        
            if (isset($this->post[$optionals_key]) && !empty($this->post[$optionals_key])) {
                $validationResult = $this->checkPostKeyValue($optionals_key, $optionals_value);
                if (!$validationResult) {
                    $errors[] = "Invalid value for field: $optionals_key";
                }
            }
        }
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->error .= $error . ', '; // Combine errors into a single string
            }
            return false;
        }
        return true;
    }

        //----------------------------PRIVATE FUNCTIONS---------------------------------------

    

    private function checkPostKeyValue(string $key, string $validation): bool
    {
        // General validation (assumed in checkValidationTypes)
        if (!$this->checkValidationTypes($validation)) {
            return false;
        }

        // Specific data type validation (using a dictionary for readability)
        $validationMap = [
            'string' => is_string($this->$key),
            'int' => is_numeric($this->$key),
            'float' => is_float($this->$key),
            'bool' => is_bool($this->$key),
            'array' => is_array($this->$key),
            'json' => (JsonHelper::validateJson($this->$key) ? true : false),
            'email' => filter_var($this->$key, FILTER_VALIDATE_EMAIL) !== false, // Explicit false check for email
        ];

        // Validate data type based on validationMap
        $result = isset($validationMap[$validation]) ? $validationMap[$validation] : false;

        if (!$result) {
            $this->error = "The field '$key' must be of type '$validation'"; // More specific error message
        }

        return $result;
    }


    /**
     * @param array<mixed> $incoming
     */
    private function convertIncomingArray(array $incoming):void
    {
        foreach ($incoming as $key => $value) {
            if(!is_array($value)) {
                $type = gettype($value);
                if($type == 'string')
                {
                    $value = $this->sanitizeInput($value);
                }
                settype($key , $type);
                $this->$key = $value;
            }
            else{

                $this->$key = [];
                foreach($incoming[$key] as $subKey => $subValue)
                {
                    $type = gettype($subValue);
                    if($type == 'string')
                    {
                        $value = $this->sanitizeInput($subValue);
                    }
                    $this->$key[$subKey] = $value;
                }
            }
        }
    }

    private function convertIncomingJson(string $json):void{
        $result = json_decode($json,true);
        if($result !== null) {
            foreach($result as $key => $value) {
                $this->$key = $value;
        }
        }
    }

     /**
     * @param  mixed $input
     * @return mixed
     */
    private function sanitizeInput(mixed $input):mixed
    {
        $input = trim($input);
        if(!is_string($input)) {
            return $input;
        }
        return filter_var($input, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }

    private function checkValidationTypes(string $validationString): bool
    {
        $validation = [
            'string',
            'int',
            'float',
            'bool',
            'array',
            'json',
            'email'
        ];
        if (!in_array($validationString, $validation)) {
            $this->error = "invalid type of validation for $validationString";
            return false;
        }
        return true;
    }


}
