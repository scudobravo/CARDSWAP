/**
 * Formatta la condizione della carta in base alla presenza di grading company
 * 
 * @param {Object} listing - L'oggetto listing con i dati della carta
 * @returns {string} - La condizione formattata
 * 
 * Formato per carte graduate: "PSA 10 – Carta: 10 – Autografo: 10"
 * Formato per carte non graduate: "Mint", "Near Mint", ecc.
 */
export function formatCondition(listing) {
  // Se c'è una grading company, usa il formato numerico
  if (listing.grading_company_id || listing.grading_company) {
    const companyName = listing.grading_company?.name || 
                       (listing.grading_company_id ? 'Graded' : '');
    const cardScore = listing.card_condition_score;
    const autographScore = listing.autograph_condition_score;
    
    if (cardScore !== null && cardScore !== undefined && cardScore !== '') {
      // Formato: "PSA 10 – Carta: 10 – Autografo: 10" oppure "PSA AUTH – Carta: AUTH – Autografo: AUTH"
      let formatted = `${companyName} ${cardScore}`;
      
      if (autographScore !== null && autographScore !== undefined && autographScore !== '') {
        formatted += ` – Carta: ${cardScore} – Autografo: ${autographScore}`;
      } else {
        // Se non c'è autograph score, mostra solo il card score
        formatted += ` – Carta: ${cardScore}`;
      }
      
      return formatted;
    }
  }
  
  // Se non c'è grading company, usa la condizione testuale
  if (listing.condition) {
    // Capitalizza la prima lettera e sostituisci gli underscore con spazi
    return listing.condition
      .split('_')
      .map(word => word.charAt(0).toUpperCase() + word.slice(1))
      .join(' ');
  }
  
  return 'Excellent'; // Default
}

/**
 * Formatta solo la condizione testuale (senza grading)
 */
export function formatTextualCondition(condition) {
  if (!condition) return 'Excellent';
  
  return condition
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
}

