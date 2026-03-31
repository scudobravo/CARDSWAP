/**
 * Normalizza un prezzo da qualsiasi formato a numero
 * Gestisce formati italiani (15.000,00) e internazionali (15000.00)
 * 
 * @param {number|string} price - Il prezzo da normalizzare
 * @returns {number} Il prezzo come numero
 */
export function normalizePrice(price) {
  if (price === null || price === undefined || price === '') {
    return 0
  }

  // Se è già un numero, restituiscilo
  if (typeof price === 'number') {
    return isNaN(price) ? 0 : price
  }

  // Converti stringa a numero, gestendo formati italiani e internazionali
  let priceStr = String(price)
    .replace(/€/g, '')
    .replace(/\s/g, '') // Rimuovi spazi
    .trim()

  // Se contiene una virgola come separatore decimale (formato italiano)
  if (priceStr.includes(',') && !priceStr.includes('.')) {
    // Formato italiano: "15.000,50" -> rimuovi punti migliaia, sostituisci virgola con punto
    priceStr = priceStr.replace(/\./g, '').replace(',', '.')
  } else if (priceStr.includes(',') && priceStr.includes('.')) {
    // Formato misto: determina quale è il separatore decimale
    // Se la virgola è dopo il punto, è formato italiano
    const lastComma = priceStr.lastIndexOf(',')
    const lastDot = priceStr.lastIndexOf('.')
    if (lastComma > lastDot) {
      // Virgola è il separatore decimale (formato italiano)
      priceStr = priceStr.replace(/\./g, '').replace(',', '.')
    } else {
      // Punto è il separatore decimale (formato internazionale)
      priceStr = priceStr.replace(/,/g, '')
    }
  } else {
    // Solo punto o solo virgola o nessuno
    priceStr = priceStr.replace(/,/g, '.')
  }

  const priceNum = parseFloat(priceStr) || 0
  return isNaN(priceNum) ? 0 : priceNum
}

/**
 * Formatta un prezzo in formato italiano
 * Esempio: 15000.00 -> "15.000,00"
 * 
 * @param {number|string} price - Il prezzo da formattare
 * @param {boolean} includeSymbol - Se true, include il simbolo € (default: false)
 * @returns {string} Il prezzo formattato
 */
export function formatPriceItaliana(price, includeSymbol = false) {
  // Normalizza il prezzo a numero prima di formattarlo
  const priceNum = normalizePrice(price)

  // Formatta con Intl.NumberFormat per formato italiano
  const formatted = new Intl.NumberFormat('it-IT', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(priceNum)

  return includeSymbol ? `€${formatted}` : formatted
}

/**
 * Valore ordine lato venditore: merce + spedizione (senza costo di gestione pagato dall'acquirente).
 * Allineato a subtotal + shipping_cost sul modello Order.
 */
export function sellerOrderGoodsAndShippingTotal(order) {
  if (!order) return 0
  const sub = normalizePrice(order.subtotal ?? order.subtotal_eur)
  const ship = normalizePrice(order.shipping_cost)
  return sub + ship
}

/**
 * Formatta un prezzo senza decimali (per quantità, ecc.)
 * Esempio: 15000 -> "15.000"
 *
 * @param {number|string} value - Il valore da formattare
 * @returns {string} Il valore formattato
 */
export function formatNumberItaliana(value) {
  if (value === null || value === undefined || value === '') {
    return '0'
  }

  const num = parseFloat(String(value).replace(/\./g, '').replace(/,/g, '.')) || 0

  return new Intl.NumberFormat('it-IT', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(num)
}

