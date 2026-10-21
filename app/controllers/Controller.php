<?php
/**
 * CampusLend Base Controller
 */

namespace App\Controllers;

abstract class Controller
{
    /**
     * Renders a view inside the main layout
     */
    protected function render(string $viewPath, array $data = [], string $layout = 'main'): void
    {
        extract($data);

        $viewFile = __DIR__ . '/../views/' . $viewPath . '.php';
        if (!file_exists($viewFile)) {
            throw new \Exception("View file [{$viewFile}] not found.");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        $layoutFile = __DIR__ . '/../views/layouts/' . $layout . '.php';
        if (!file_exists($layoutFile)) {
            // Output view directly if layout does not exist
            echo $content;
            return;
        }

        require $layoutFile;
    }

    /**
     * Sends a JSON response with proper HTTP status
     */
    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Validates input fields against rules
     */
    protected function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleList) {
            $value = trim((string)($data[$field] ?? ''));
            $fieldRules = explode('|', $ruleList);

            foreach ($fieldRules as $rule) {
                if ($rule === 'required' && $value === '') {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
                    break;
                }

                if ($rule === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "Please enter a valid email address.";
                    break;
                }

                if (str_starts_with($rule, 'min:') && $value !== '') {
                    $min = (int)substr($rule, 4);
                    if (mb_strlen($value) < $min) {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be at least {$min} characters.";
                        break;
                    }
                }

                if (str_starts_with($rule, 'max:') && $value !== '') {
                    $max = (int)substr($rule, 4);
                    if (mb_strlen($value) > $max) {
                        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " cannot exceed {$max} characters.";
                        break;
                    }
                }
            }
        }

        return $errors;
    }
}
