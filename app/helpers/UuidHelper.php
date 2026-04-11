<?php
declare(strict_types=1);

use Symfony\Component\Uid\Uuid;

/**
 * Classe auxiliar para geração de UUIDs
 * 
 * Centraliza a geração de IDs únicos para toda a aplicação.
 * Usa Symfony\Uid\Uuid para gerar UUIDs v4 (aleatórios) confiáveis.
 * 
 * Vantagens:
 * - Formato padrão e reconhecido internacionalmente
 * - Compatibilidade com bases de dados modernas
 * - Segurança criptográfica garantida
 * - Fácil de debugar (visível em logs)
 */
final class UuidHelper
{
    /**
     * Gera um novo UUID v4 (aleatório)
     * 
     * @return string UUID em formato hexadecimal de 32 caracteres (ex: "550e8400e29b41d4a716446655440000")
     * 
     * Exemplo de uso:
     * $id = UuidHelper::generate();
     */
    public static function generate(): string
    {
        $uuid = Uuid::v4();
        // Remove os hífens do formato padrão para obter formato hexadecimal de 32 chars
        return str_replace('-', '', (string)$uuid);
    }

    /**
     * Gera um novo UUID v4 com separadores padrão
     * 
     * @return string UUID em formato padrão (ex: "550e8400-e29b-41d4-a716-446655440000")
     * 
     * Use esta versão se preferir o formato padrão com hífens
     */
    public static function generateStandard(): string
    {
        return (string)Uuid::v4();
    }

    /**
     * Valida se uma string é um UUID válido
     * 
     * @param string $uuid UUID para validar (aceita formato hexadecimal ou com hífens)
     * @return bool true se for UUID válido, false caso contrário
     */
    public static function isValid(string $uuid): bool
    {
        try {
            // Remove hífens se existirem para validar ambos os formatos
            $cleanUuid = str_replace('-', '', $uuid);
            
            // Se tem 32 caracteres é hexadecimal, adiciona hífens para validar
            if (strlen($cleanUuid) === 32) {
                $uuid = substr($cleanUuid, 0, 8) . '-' . 
                        substr($cleanUuid, 8, 4) . '-' . 
                        substr($cleanUuid, 12, 4) . '-' . 
                        substr($cleanUuid, 16, 4) . '-' . 
                        substr($cleanUuid, 20);
            }
            
            Uuid::fromString($uuid);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
