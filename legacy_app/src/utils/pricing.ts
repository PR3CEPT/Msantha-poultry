/**
 * Msantha Poultry Farm - Dynamic Live Bird Pricing Matrix
 * Pricing formulas for Mikolongwe, Sasso, Quails, Ducks, and Turkeys
 * Based on Age in Weeks and Day-Old status (MWK currency)
 */

export interface PricePoint {
  week: number;
  ageLabel: string;
  priceMwk: number;
  formulaNote?: string;
}

export interface SpeciesPricingDefinition {
  speciesKey: string;
  displayName: string;
  dayOldPriceMwk: number;
  description: string;
  calculate: (ageWeeks: number, isDayOld?: boolean) => number;
}

export const POULTRY_PRICING_CONFIG: Record<string, SpeciesPricingDefinition> = {
  mikolongwe: {
    speciesKey: 'mikolongwe',
    displayName: 'Mikolongwe Chickens',
    dayOldPriceMwk: 3000,
    description: 'Day-old @ MK3,000; Wk 1-12 +MK500/wk; Wk 13 MK15,000; Wk 14-20 +MK3,000/wk',
    calculate: (ageWeeks: number, isDayOld = false): number => {
      if (isDayOld || ageWeeks <= 0) return 3000;
      if (ageWeeks >= 1 && ageWeeks <= 12) {
        return 3000 + ageWeeks * 500;
      }
      if (ageWeeks === 13) {
        return 15000;
      }
      // 14 to 20th week: increases by MK 3000 from 13 weeks
      const weeksOver13 = ageWeeks - 13;
      return 15000 + weeksOver13 * 3000;
    },
  },

  sasso: {
    speciesKey: 'sasso',
    displayName: 'Sasso Chickens',
    dayOldPriceMwk: 4000,
    description: 'Day-old @ MK4,000; Wk 1-9 +MK500/wk; Wk 10-20 +MK2,500/wk',
    calculate: (ageWeeks: number, isDayOld = false): number => {
      if (isDayOld || ageWeeks <= 0) return 4000;
      if (ageWeeks >= 1 && ageWeeks <= 9) {
        return 4000 + ageWeeks * 500;
      }
      // Wk 9 is 4000 + 9*500 = 8500
      const week9Price = 4000 + 9 * 500;
      const weeksOver9 = ageWeeks - 9;
      return week9Price + weeksOver9 * 2500;
    },
  },

  quail: {
    speciesKey: 'quail',
    displayName: 'Quails',
    dayOldPriceMwk: 1000,
    description: 'Day-old @ MK1,000; Wk 1-10 +MK500/wk',
    calculate: (ageWeeks: number, isDayOld = false): number => {
      if (isDayOld || ageWeeks <= 0) return 1000;
      const weeksClamped = Math.min(ageWeeks, 10);
      return 1000 + weeksClamped * 500;
    },
  },

  duck: {
    speciesKey: 'duck',
    displayName: 'Ducks',
    dayOldPriceMwk: 10000,
    description: 'Day-old @ MK10,000; Wk 1-15 +MK2,000/wk; Wk 16-20 +MK5,000/wk',
    calculate: (ageWeeks: number, isDayOld = false): number => {
      if (isDayOld || ageWeeks <= 0) return 10000;
      if (ageWeeks >= 1 && ageWeeks <= 15) {
        return 10000 + ageWeeks * 2000;
      }
      // Week 15 price: 10000 + 15 * 2000 = 40000
      const week15Price = 10000 + 15 * 2000;
      const weeksOver15 = ageWeeks - 15;
      return week15Price + weeksOver15 * 5000;
    },
  },

  turkey: {
    speciesKey: 'turkey',
    displayName: 'Turkeys',
    dayOldPriceMwk: 12000,
    description: 'Day-old @ MK12,000; Wk 1 MK15,000; Wk 2-20 +MK5,000/wk',
    calculate: (ageWeeks: number, isDayOld = false): number => {
      if (isDayOld || ageWeeks <= 0) return 12000;
      if (ageWeeks === 1) return 15000;
      // Wk 2-20 increases by 5000 from Wk 1
      const weeksOver1 = ageWeeks - 1;
      return 15000 + weeksOver1 * 5000;
    },
  },

  guinea_fowl: {
    speciesKey: 'guinea_fowl',
    displayName: 'Guinea Fowl (Nkhanga)',
    dayOldPriceMwk: 4500,
    description: 'Day-old (Keets) @ MK4,500; Wk 1-12 +MK1,000/wk; Wk 13-20 +MK1,500/wk',
    calculate: (ageWeeks: number, isDayOld = false): number => {
      if (isDayOld || ageWeeks <= 0) return 4500;
      if (ageWeeks >= 1 && ageWeeks <= 12) {
        return 4500 + ageWeeks * 1000;
      }
      const week12Price = 4500 + 12 * 1000;
      const weeksOver12 = ageWeeks - 12;
      return week12Price + weeksOver12 * 1500;
    },
  },
};

/**
 * Normalizes user / flock species string to pricing key
 */
export function normalizeSpeciesToPricingKey(rawSpecies: string): string | null {
  const s = (rawSpecies || '').toLowerCase().trim();
  if (s.includes('mikolongwe')) return 'mikolongwe';
  if (s.includes('sasso')) return 'sasso';
  if (s.includes('quail') || s.includes('qual')) return 'quail';
  if (s.includes('duck')) return 'duck';
  if (s.includes('turkey')) return 'turkey';
  if (s.includes('guinea') || s.includes('nkhanga')) return 'guinea_fowl';
  return null;
}

/**
 * Calculates official selling price in MWK for a live bird given species and age
 */
export function calculateBirdPrice(
  species: string,
  ageWeeks: number,
  isDayOld = false
): number {
  const key = normalizeSpeciesToPricingKey(species);
  if (!key || !POULTRY_PRICING_CONFIG[key]) {
    // Fallback for Local Chicken or unspecified species
    if (isDayOld || ageWeeks <= 0) return 3000;
    return Math.min(12000, 3000 + ageWeeks * 600);
  }
  return POULTRY_PRICING_CONFIG[key].calculate(Math.max(0, ageWeeks), isDayOld);
}

/**
 * Generates full rate card lookup table for a given species up to 20 weeks
 */
export function getSpeciesRateCard(speciesKey: string): PricePoint[] {
  const key = normalizeSpeciesToPricingKey(speciesKey) || 'mikolongwe';
  const config = POULTRY_PRICING_CONFIG[key];
  if (!config) return [];

  const points: PricePoint[] = [
    {
      week: 0,
      ageLabel: 'Day-Old (DOC)',
      priceMwk: config.dayOldPriceMwk,
      formulaNote: 'Initial Day-Old Rate',
    },
  ];

  const maxWeeks = key === 'quail' ? 10 : 20;

  for (let w = 1; w <= maxWeeks; w++) {
    const price = config.calculate(w, false);
    let note = '';
    if (key === 'mikolongwe') {
      if (w <= 12) note = '+MK500/wk';
      else if (w === 13) note = 'Special Milestone (MK15,000)';
      else note = '+MK3,000/wk';
    } else if (key === 'sasso') {
      if (w <= 9) note = '+MK500/wk';
      else note = '+MK2,500/wk';
    } else if (key === 'quail') {
      note = '+MK500/wk';
    } else if (key === 'duck') {
      if (w <= 15) note = '+MK2,000/wk';
      else note = '+MK5,000/wk';
    } else if (key === 'turkey') {
      if (w === 1) note = 'Week 1 Base (MK15,000)';
      else note = '+MK5,000/wk';
    } else if (key === 'guinea_fowl') {
      if (w <= 12) note = '+MK1,000/wk';
      else note = '+MK1,500/wk';
    }

    points.push({
      week: w,
      ageLabel: `Week ${w} (${w * 7} Days)`,
      priceMwk: price,
      formulaNote: note,
    });
  }

  return points;
}

/**
 * Calculates age in weeks from dateAcquired string (YYYY-MM-DD)
 */
export function calculateAgeInWeeks(dateAcquired: string): number {
  if (!dateAcquired) return 1;
  const acquired = new Date(dateAcquired).getTime();
  const now = Date.now();
  const diffDays = Math.max(0, Math.floor((now - acquired) / (1000 * 60 * 60 * 24)));
  return Math.floor(diffDays / 7);
}

/**
 * Computes flock asset valuation based on current quantity, species, and age in weeks
 */
export function getFlockValuation(flock: {
  species: string;
  dateAcquired: string;
  currentQuantity: number;
  ageGroup?: string;
}): { ageWeeks: number; unitPriceMwk: number; totalValuationMwk: number; isDayOld: boolean } {
  const isDayOld = flock.ageGroup === 'day_old';
  const ageWeeks = calculateAgeInWeeks(flock.dateAcquired);
  const unitPriceMwk = calculateBirdPrice(flock.species, ageWeeks, isDayOld);
  const totalValuationMwk = unitPriceMwk * (Number(flock.currentQuantity) || 0);

  return {
    ageWeeks,
    unitPriceMwk,
    totalValuationMwk,
    isDayOld,
  };
}
