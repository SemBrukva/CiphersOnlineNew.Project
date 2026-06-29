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
import { transformNumbersToLetters, looksLikeNumbersToLetters } from './decoders/numbers-to-letters.js'
import { transformHtml, looksLikeHtml } from './decoders/html.js'
import { transformJson, looksLikeJson } from './decoders/json.js'
import { transformHash, looksLikeHash } from './decoders/hash.js'
import { transformHmac, looksLikeHmac } from './decoders/hmac.js'
import { transformPbkdf2, looksLikePbkdf2 } from './decoders/pbkdf2.js'
import { transformBcrypt, looksLikeBcrypt } from './decoders/bcrypt.js'
import { transformArgon2, looksLikeArgon2 } from './decoders/argon2.js'

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
  'codes-and-alphabets/numbers-to-letters': { transform: transformNumbersToLetters, looksLikeEncoded: looksLikeNumbersToLetters },
  'encoding/html-encode': { transform: transformHtml, looksLikeEncoded: looksLikeHtml },
  'encoding/json-formatter': { transform: transformJson, looksLikeEncoded: looksLikeJson },
  'hashing/sha256': { transform: transformHash, looksLikeEncoded: looksLikeHash },
  'hashing/sha1': { transform: transformHash, looksLikeEncoded: looksLikeHash },
  'hashing/sha512': { transform: transformHash, looksLikeEncoded: looksLikeHash },
  'hashing/md5': { transform: transformHash, looksLikeEncoded: looksLikeHash },
  'hashing/sha3-256': { transform: transformHash, looksLikeEncoded: looksLikeHash },
  'hashing/sha3-512': { transform: transformHash, looksLikeEncoded: looksLikeHash },
  'hashing/blake2b': { transform: transformHash, looksLikeEncoded: looksLikeHash },
  'hashing/crc32': { transform: transformHash, looksLikeEncoded: looksLikeHash },
  'hashing/hmac': { transform: transformHmac, looksLikeEncoded: looksLikeHmac },
  'hashing/pbkdf2': { transform: transformPbkdf2, looksLikeEncoded: looksLikePbkdf2 },
  'hashing/bcrypt': { transform: transformBcrypt, looksLikeEncoded: looksLikeBcrypt },
  'hashing/argon2': { transform: transformArgon2, looksLikeEncoded: looksLikeArgon2 },
}

/**
 * Возвращает декодер по slug инструмента.
 */
export function getDecoderBySlug(slug) {
  return DECODER_BY_SLUG[slug] ?? null
}
