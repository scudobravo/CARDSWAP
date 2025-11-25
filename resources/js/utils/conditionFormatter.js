/**
 * Formatta la condizione della carta in base alla presenza di grading company
 * 
 * @param {Object} listing - L'oggetto listing con i dati della carta
 * @returns {string} - La condizione formattata
 * 
 * Formato per carte graduate: 
 * - Con entrambi i voti: "PSA 8 – Autografo: 10"
 * - Solo carta: "PSA 8"
 * - Solo autografo: "PSA – Autografo: 10"
 * Formato per carte non graduate: "Mint", "Near Mint", ecc.
 */
export function formatCondition(listing) {
  // Se c'è una grading company, usa il formato numerico
  if (listing.grading_company_id || listing.grading_company) {
    const companyName = listing.grading_company?.name || 
                       (listing.grading_company_id ? 'Graded' : '');
    const cardScore = listing.card_condition_score;
    const autographScore = listing.autograph_condition_score;
    
    // Verifica se ci sono score validi
    const hasCardScore = cardScore !== null && cardScore !== undefined && cardScore !== '';
    const hasAutographScore = autographScore !== null && autographScore !== undefined && autographScore !== '';
    
    if (hasCardScore || hasAutographScore) {
      // Se ci sono entrambi i voti: "PSA 8 – Autografo: 10"
      if (hasCardScore && hasAutographScore) {
        return `${companyName} ${cardScore} – Autografo: ${autographScore}`;
      }
      
      // Se c'è solo il voto carta: "PSA 8"
      if (hasCardScore) {
        return `${companyName} ${cardScore}`;
      }
      
      // Se c'è solo il voto autografo: "PSA – Autografo: 10"
      if (hasAutographScore) {
        return `${companyName} – Autografo: ${autographScore}`;
      }
    }
    
    // Se c'è grading company ma non ci sono score, mostra solo il nome della company
    if (companyName) {
      return companyName;
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

