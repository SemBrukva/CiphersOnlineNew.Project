<?php

declare(strict_types=1);

namespace App\Geo;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use MaxMind\Db\Reader\InvalidDatabaseException;

/**
 * Определяет ISO-код страны по IP-адресу через локальную базу GeoLite2-Country.
 *
 * Reader создаётся лениво и переиспользуется в рамках одного запроса (синглтон в контейнере).
 * При отсутствии файла базы или невалидном IP возвращает null без исключений.
 */
final class GeoIpService
{
    private ?Reader $reader = null;
    private bool    $initialized = false;

    /**
     * @param string $dbPath   Путь к файлу GeoLite2-Country.mmdb.
     * @param bool   $enabled  Флаг включения геолокации (для отключения без удаления файла).
     */
    public function __construct(
        private readonly string $dbPath,
        private readonly bool   $enabled = true,
    ) {
    }

    /**
     * Возвращает двухбуквенный ISO 3166-1 alpha-2 код страны для переданного IP.
     * Возвращает null, если геолокация отключена, IP приватный/невалидный или база недоступна.
     */
    public function getCountryCode(string $ip): ?string
    {
        if (!$this->enabled) {
            return null;
        }

        $reader = $this->getReader();

        if ($reader === null) {
            return null;
        }

        try {
            return $reader->country($ip)->country->isoCode;
        } catch (AddressNotFoundException) {
            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Ленивый геттер Reader — создаётся один раз, повторно используется.
     */
    private function getReader(): ?Reader
    {
        if ($this->initialized) {
            return $this->reader;
        }

        $this->initialized = true;

        if (!is_file($this->dbPath)) {
            return null;
        }

        try {
            $this->reader = new Reader($this->dbPath);
        } catch (InvalidDatabaseException) {
            $this->reader = null;
        }

        return $this->reader;
    }
}
