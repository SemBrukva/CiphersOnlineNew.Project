import { transformAscii, looksLikeAscii } from './decoders/ascii.js'
import { transformBase64, looksLikeBase64 } from './decoders/base64.js'
import { transformBinary, looksLikeBinary } from './decoders/binary.js'
import { transformHex, looksLikeHex } from './decoders/hex.js'
import { transformJwt, looksLikeJwt } from './decoders/jwt.js'
import { transformUnicode, looksLikeUnicode } from './decoders/unicode.js'
import { transformUrl, looksLikeUrl } from './decoders/url.js'
import { transformMorse, looksLikeMorse } from './decoders/morse.js'
import { transformFrequency, looksLikeText } from './decoders/frequency.js'
import { transformLetterFrequency, looksLikeLetterFreqText } from './decoders/letter-frequency.js'

/**
 * Реестр browser-декодеров по slug инструмента.
 */
const DECODER_BY_SLUG = {
  'encoding/base64': { transform: transformBase64, looksLikeEncoded: looksLikeBase64 },
  'encoding/hex': { transform: transformHex, looksLikeEncoded: looksLikeHex },
  'encoding/binary-converter': { transform: transformBinary, looksLikeEncoded: looksLikeBinary },
  'encoding/url-encode': { transform: transformUrl, looksLikeEncoded: looksLikeUrl },
  'encoding/ascii-converter': { transform: transformAscii, looksLikeEncoded: looksLikeAscii },
  'encoding/unicode-converter': { transform: transformUnicode, looksLikeEncoded: looksLikeUnicode },
  'encoding/jwt-decoder': { transform: transformJwt, looksLikeEncoded: looksLikeJwt },
  'codes-and-alphabets/morse-code': { transform: transformMorse, looksLikeEncoded: looksLikeMorse },
  'text-analysis/frequency-analysis': { transform: transformFrequency, looksLikeEncoded: looksLikeText },
  'text-analysis/letter-frequency': { transform: transformLetterFrequency, looksLikeEncoded: looksLikeLetterFreqText },
}

/**
 * Возвращает декодер по slug инструмента.
 */
export function getDecoderBySlug(slug) {
  return DECODER_BY_SLUG[slug] ?? null
}
