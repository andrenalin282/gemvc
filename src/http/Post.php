<?php
namespace GemLibrary\Http;

class Post
{

    /**
     * @param string|array<mixed> $data
     */
    public function __construct(string|array $data)
    {
        if (!is_array($data)) {
            $data = json_decode($data);
        }
        if(is_array($data)) {
            $this->convertIncomingArray($data);
        }
    }

    //----------------------------PRIVATE FUNCTIONS---------------------------------------

    /**
     * @param array<mixed> $incoming
     */
    private function convertIncomingArray(array $incoming): void
    {
        foreach ($incoming as $key => $value) {
            if (!is_array($value)) {
                $type = gettype($value);
                if ($type == 'string') {
                    $value = $this->sanitizeInput($value);
                }
                settype($key, $type);
                $this->$key = $value;
            } else {

                $this->$key = [];
                /**@phpstan-ignore-next-line */
                foreach ($incoming[$key] as $subKey => $subValue) {
                    $type = gettype($subValue);
                    if ($type == 'string') {
                        $value = $this->sanitizeInput($subValue);
                    }
                    $this->$key[$subKey] = $value;
                }
            }
        }
    }

    /**
     * @param  mixed $input
     * @return mixed
     */
    private function sanitizeInput(mixed $input): mixed
    {
        if (!is_string($input)) {
            return $input;
        }
        return filter_var($input, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }

}
