<?php

namespace SprintPHP\Lib\Validator;

use App\Api\Exception\ValidationException;
use Exception;
use ReflectionClass;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class DTO
{
    private ValidatorInterface $validator;

    public function __construct()
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }


    /**
     * Valida os dados recebidos de acordo com as regras definidas na classe DTO.
     *
     * @param string      $classDTO    Nome da classe DTO (com namespace completo).
     * @param array       $data        Dados a serem validados.
     * @param array|null  $constraints Restrições personalizadas (opcional).
     * @param array|null  $groups      Grupos de validação (opcional).
     *
     * @return array|object Retorna um objeto com os dados se válidos, ou um array com mensagens de erro.
     *
     * @throws Exception Se houverem erros de validação.
     *
     * @author Augusto Ribeiro
     */
    public function validateData(string $classDTO, array $data, ?array $constraints = null, ?array $groups = null,  bool $flApi = false): array|object
    {
        $dto = new $classDTO();


        if ($flApi)
        {
            $reflection = new ReflectionClass($dto);

            foreach ($data as $key => $value)
            {
                if ($reflection->hasProperty($key))
                {
                    $property = $reflection->getProperty($key);
                    $property->setValue($dto, $value);
                }
            }
        }
        else
        {
            foreach ($dto as $key => $item)
            {
                if (array_key_exists($key, $data))
                {
                    $dto->{$key} = $data[$key];
                }
                else if (isset($item) && $item != "")
                {
                    $dto->{$item} = null;
                }
            }
        }


        $errors = $this->validator->validate($dto, $constraints, $groups);

        if (count($errors) > 0)
        {
            if ($flApi)
            {
                throw new ValidationException($this->formatarErrosApi($errors));
            }

            return $this->formatarErros($errors);
        }

        return (object) $data;
    }


    /**
     * Formata os erros de validação em um array associativo e lança uma exceção.
     *
     * @param ConstraintViolationListInterface $violations Lista de violações de validação.
     *
     * @return array Nunca retorna, pois lança exceção.
     *
     * @throws Exception Com as mensagens de erro formatadas.
     *
     * @author Augusto Ribeiro
     */
    private function formatarErros(ConstraintViolationListInterface $violations): array
    {
        $errors = [];
        foreach ($violations as $violation)
        {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        $messageError = '';

        $messageError .= implode(', <br>', $errors);

        throw new Exception($messageError);
    }


    /**
     * Formata os erros de validação em um array associativo e lança uma exceção para api.
     *
     * @param ConstraintViolationListInterface $violations Lista de violações de validação.
     *
     * @return array Nunca retorna, pois lança exceção.
     *
     * @throws Exception Com as mensagens de erro formatadas.
     *
     * @author Ramon Barbosa
     */
    private function formatarErrosApi($errors): array
    {
        $fields = [];

        foreach ($errors as $error)
        {
            $fields[$error->getPropertyPath()] = $error->getMessage();
        }

        return $fields;
    }
}
