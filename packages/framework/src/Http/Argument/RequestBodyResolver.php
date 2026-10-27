<?php

declare(strict_types=1);

namespace Winter\Http\Argument;

use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Winter\Attribute\RequestBody;
use Winter\Http\HttpException\BadRequestHttpException;
use Winter\Http\HttpException\ValidationException;
use Winter\Http\RequestContext;
use Winter\Support\Reflection;
use Winter\Validation\ValidationErrors;

final class RequestBodyResolver implements ArgumentResolverInterface
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function supports(\ReflectionParameter $parameter, RequestContext $context): bool
    {
        return Reflection::attribute($parameter, RequestBody::class) !== null;
    }

    public function resolve(\ReflectionParameter $parameter, RequestContext $context): mixed
    {
        $class = Reflection::typeName($parameter);

        if ($class === null || !class_exists($class)) {
            throw new BadRequestHttpException(sprintf(
                '#[RequestBody] parameter $%s must be typed with a class.',
                $parameter->getName(),
            ));
        }

        $content = (string) $context->request->getContent();
        $data = $content === '' ? [] : json_decode($content, true);

        if ($content !== '' && json_last_error() !== JSON_ERROR_NONE) {
            throw new BadRequestHttpException('Malformed JSON body: ' . json_last_error_msg() . '.');
        }

        if (!\is_array($data)) {
            throw new BadRequestHttpException('JSON body must be an object.');
        }

        try {
            $dto = $this->serializer->deserialize(
                json_encode($data, JSON_THROW_ON_ERROR),
                $class,
                'json',
                ['disable_type_enforcement' => true],
            );
        } catch (SerializerException|\JsonException $exception) {
            throw new BadRequestHttpException('Unable to map JSON body.', $exception);
        }

        /** @var RequestBody $attribute */
        $attribute = Reflection::attribute($parameter, RequestBody::class);

        if ($attribute->validate) {
            $violations = $this->validator->validate($dto);

            if (\count($violations) > 0) {
                throw new ValidationException(ValidationErrors::flatten($violations));
            }
        }

        return $dto;
    }
}
