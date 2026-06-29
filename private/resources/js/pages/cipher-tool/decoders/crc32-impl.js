/**
 * Чистая JS-реализация CRC-32 (IEEE 802.3 / ISO 3309 / PNG / ZIP).
 * Полином 0xEDB88320 — отражённое представление 0x04C11DB7.
 * Возвращает 8-символьную hex-строку нижнего регистра.
 */

const CRC32_TABLE = (() => {
  const table = new Uint32Array(256)
  for (let i = 0; i < 256; i++) {
    let c = i
    for (let j = 0; j < 8; j++) {
      c = (c & 1) ? (0xEDB88320 ^ (c >>> 1)) : (c >>> 1)
    }
    table[i] = c
  }
  return table
})()

/**
 * Вычисляет CRC32 от строки (UTF-8 байты). Возвращает hex-строку (8 символов).
 */
export function crc32(text) {
  const bytes = new TextEncoder().encode(String(text ?? ''))
  let crc = 0xFFFFFFFF
  for (let i = 0; i < bytes.length; i++) {
    crc = CRC32_TABLE[(crc ^ bytes[i]) & 0xFF] ^ (crc >>> 8)
  }
  return ((crc ^ 0xFFFFFFFF) >>> 0).toString(16).padStart(8, '0')
}
