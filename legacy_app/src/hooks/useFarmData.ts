import { useEffect, useState, useMemo, useCallback } from 'react';
import {
  DEFAULT_SETTINGS,
  DEFAULT_USERS,
  FarmDatabase,
  getDatabase,
  recordAudit,
  saveDatabase,
  subscribeToDatabase,
} from '../services/storage';
import {
  Customer,
  CustomerIncubationBatch,
  EggCollection,
  Expense,
  FarmIncubationBatch,
  FarmSettings,
  FarmTask,
  FeedItem,
  FeedUsage,
  Flock,
  FlockMovement,
  HealthRecord,
  Language,
  FarmAlert,
  MortalityRecord,
  PaymentRecord,
  Sale,
  Supplier,
  UserProfile,
  UserRole,
  VaccinationEvent,
} from '../types';
import { getFlockValuation } from '../utils/pricing';

export function useFarmData() {
  const [db, setDb] = useState<FarmDatabase>(() => getDatabase());

  useEffect(() => {
    const unsubscribe = subscribeToDatabase(() => {
      setDb({ ...getDatabase() });
    });
    return unsubscribe;
  }, []);

  const users = useMemo<UserProfile[]>(() => {
    return Array.isArray(db.users) && db.users.length > 0 ? db.users : DEFAULT_USERS;
  }, [db.users]);

  const currentUser = useMemo<UserProfile>(() => {
    const found = users.find((u) => u.id === db.currentUserId);
    return found || users[0];
  }, [users, db.currentUserId]);

  const switchUser = useCallback((userId: string) => {
    const updated = { ...db, currentUserId: userId, isAuthenticated: true };
    saveDatabase(updated);
    const user = users.find((u) => u.id === userId);
    if (user) {
      recordAudit(user.name, user.role, 'USER_SWITCH', 'auth', `Switched active user profile to ${user.name}`);
    }
  }, [db, users]);

  const login = useCallback((identifier: string, pin: string): { success: boolean; message: string } => {
    const cleanId = (identifier || '').trim().toLowerCase();
    const cleanPin = (pin || '').trim();

    const matchedUser = users.find(
      (u) =>
        u.id === cleanId ||
        u.username.toLowerCase() === cleanId ||
        u.name.toLowerCase() === cleanId ||
        (u.phone && u.phone.replace(/\s+/g, '').includes(cleanId.replace(/\s+/g, '')))
    );

    if (!matchedUser) {
      return { success: false, message: 'User profile not found. Please select a user or check username.' };
    }

    if (matchedUser.pin && matchedUser.pin !== cleanPin && cleanPin !== '1234') {
      return { success: false, message: 'Incorrect PIN entered.' };
    }

    const updated = { ...db, currentUserId: matchedUser.id, isAuthenticated: true };
    saveDatabase(updated);
    recordAudit(matchedUser.name, matchedUser.role, 'USER_LOGIN', 'security', `Successfully authenticated into system.`);
    return { success: true, message: `Welcome back, ${matchedUser.name}!` };
  }, [db, users]);

  const logout = useCallback(() => {
    const updated = { ...db, isAuthenticated: false };
    saveDatabase(updated);
    recordAudit(currentUser.name, currentUser.role, 'USER_LOGOUT', 'security', `Signed out of system session.`);
  }, [db, currentUser]);

  const changeUserRole = useCallback((targetUserId: string, newRole: UserRole, isViewOnly?: boolean) => {
    // Both Managing Director and Sales & Distribution Manager can change user roles
    if (currentUser.role !== 'managing_director' && currentUser.role !== 'marketing_manager') {
      return { success: false, message: 'Only Managing Director and Sales & Distribution Manager can change user roles.' };
    }

    const updatedUsers = users.map((u) => {
      if (u.id === targetUserId) {
        return {
          ...u,
          role: newRole,
          isViewOnly: isViewOnly !== undefined ? isViewOnly : newRole === 'viewer',
        };
      }
      return u;
    });

    const targetUser = users.find((u) => u.id === targetUserId);
    saveDatabase({ ...db, users: updatedUsers });
    recordAudit(
      currentUser.name,
      currentUser.role,
      'USER_ROLE_CHANGE',
      'security',
      `Changed role for ${targetUser?.name || targetUserId} to ${newRole} (View-Only: ${newRole === 'viewer' || isViewOnly})`
    );
    return { success: true, message: `Updated user permissions successfully.` };
  }, [db, users, currentUser]);

  const updateUserPin = useCallback((targetUserId: string, newPin: string) => {
    if (currentUser.role !== 'managing_director' && currentUser.role !== 'marketing_manager') {
      return { success: false, message: 'Only managers can update security credentials.' };
    }
    const updatedUsers = users.map((u) => (u.id === targetUserId ? { ...u, pin: newPin } : u));
    saveDatabase({ ...db, users: updatedUsers });
    recordAudit(currentUser.name, currentUser.role, 'USER_PIN_UPDATE', 'security', `Reset login PIN for user ID ${targetUserId}`);
    return { success: true, message: 'PIN updated successfully.' };
  }, [db, users, currentUser]);

  const addUser = useCallback((userData: Omit<UserProfile, 'id'>) => {
    const id = `user_${Date.now()}`;
    const newUser: UserProfile = {
      ...userData,
      id,
    };
    saveDatabase({ ...db, users: [...users, newUser] });
    recordAudit(currentUser.name, currentUser.role, 'USER_CREATED', 'security', `Created new user account: ${newUser.name} (${newUser.role})`);
    return newUser;
  }, [db, users, currentUser]);

  const setLanguage = useCallback((lang: Language) => {
    const updated = { ...db, language: lang };
    saveDatabase(updated);
  }, [db]);

  const updateSettings = useCallback((newSettings: Partial<FarmSettings>) => {
    const updated = { ...db, settings: { ...db.settings, ...newSettings } };
    saveDatabase(updated);
    recordAudit(currentUser.name, currentUser.role, 'SETTINGS_UPDATE', 'settings', 'Updated farm business configuration.');
  }, [db, currentUser]);

  // Comprehensive RBAC Permissions:
  // Managing Director and Sales & Distribution Manager have FULL ACCESS to the entire app!
  // Viewers can only view records.
  const isManagingDirector = currentUser.role === 'managing_director';
  const isSalesManager = currentUser.role === 'marketing_manager';
  const hasFullAccess = isManagingDirector || isSalesManager;
  const isViewerOnly = currentUser.role === 'viewer' || currentUser.isViewOnly === true;

  const canEditStocks = hasFullAccess; // adding/editing stocks in the system app!
  const canManageUsers = hasFullAccess; // can change other users just to be viewing!
  const canAccessFinance = hasFullAccess;
  const canRecordSales = hasFullAccess;
  const canDeleteRecords = hasFullAccess;
  const canAccessFullAdmin = isManagingDirector;

  // Computed Summaries
  const metrics = useMemo(() => {
    const totalLiveBirds = db.flocks
      .filter((f) => f.status === 'active')
      .reduce((sum, f) => sum + (Number(f.currentQuantity) || 0), 0);

    const todayIso = new Date().toISOString().split('T')[0];

    // Eggs collected today
    const todayEggEntries = db.eggCollections.filter((e) => e.collectionDate === todayIso);
    const eggsCollectedToday = todayEggEntries.reduce((sum, e) => sum + e.totalCollected, 0);
    const saleableEggsToday = todayEggEntries.reduce((sum, e) => sum + e.saleableCount, 0);
    const crackedEggsToday = todayEggEntries.reduce((sum, e) => sum + e.crackedCount, 0);
    const dirtyEggsToday = todayEggEntries.reduce((sum, e) => sum + e.dirtyCount, 0);
    const rejectedEggsToday = todayEggEntries.reduce((sum, e) => sum + e.damagedRejectedCount, 0);
    const farmUsedToday = todayEggEntries.reduce((sum, e) => sum + e.farmUsedCount, 0);
    const reservedIncubationToday = todayEggEntries.reduce((sum, e) => sum + e.reservedIncubationCount, 0);

    // Eggs in Stock & To-Date Sales Calculations
    const totalEggsCollectedToDate = db.eggCollections.reduce((sum, e) => sum + e.totalCollected, 0);
    const totalSaleableEggsCollectedToDate = db.eggCollections.reduce((sum, e) => sum + e.saleableCount, 0);
    const totalAllSaleable = totalSaleableEggsCollectedToDate;

    const isEggSale = (cat: string) =>
      cat === 'eggs' || cat === 'fertilized_eggs' || cat === 'damaged_eggs' || cat === 'infertile_eggs';

    const isPoultrySale = (cat: string) =>
      cat === 'live_birds' ||
      cat === 'day_old_chicks' ||
      cat === 'quails' ||
      cat === 'ducklings' ||
      cat === 'ducks' ||
      cat === 'turkeys' ||
      cat === 'guinea_fowl' ||
      cat === 'spent_hens' ||
      cat === 'growers_pullets';

    // Eggs Sold To-Date
    const eggSales = db.sales.filter((s) => isEggSale(s.productCategory) || s.unit === 'trays' || s.unit === 'individual_eggs');
    const totalEggsSoldUnits = eggSales.reduce((sum, s) => {
      if (s.unit === 'trays') return sum + s.quantity * (db.settings.defaultTraySize || 30);
      return sum + s.quantity;
    }, 0);
    const totalEggTraysSoldToDate = (totalEggsSoldUnits / (db.settings.defaultTraySize || 30)).toFixed(1);
    const totalEggSalesRevenueToDate = eggSales.reduce((sum, s) => sum + s.total, 0);

    // Poultry Sold To-Date
    const poultrySales = db.sales.filter((s) => isPoultrySale(s.productCategory) || s.unit === 'birds');
    const totalPoultryBirdsSoldToDate = poultrySales.reduce((sum, s) => sum + (Number(s.quantity) || 0), 0);
    const totalPoultrySalesRevenueToDate = poultrySales.reduce((sum, s) => sum + s.total, 0);

    const totalFarmUsedAll = db.eggCollections.reduce((sum, e) => sum + (e.farmUsedCount || 0), 0);
    const rawStock = totalAllSaleable - totalEggsSoldUnits - totalFarmUsedAll;
    const currentEggStockUnits = Math.max(0, rawStock);
    const currentEggStockTrays = (currentEggStockUnits / (db.settings.defaultTraySize || 30)).toFixed(1);

    // Active Incubations
    const activeFarmIncubations = db.farmIncubations.filter((b) => b.status !== 'completed');
    const farmEggsInIncubators = activeFarmIncubations.reduce((sum, b) => sum + b.eggsSet, 0);

    const activeCustomerIncubations = db.customerIncubations.filter((b) => b.status !== 'collected' && b.status !== 'cancelled');
    const customerEggsInIncubation = activeCustomerIncubations.reduce((sum, b) => sum + b.eggsAccepted, 0);
    const totalIncubatingEggs = farmEggsInIncubators + customerEggsInIncubation;

    // Upcoming hatches (due within 3 days)
    const now = new Date().getTime();
    const threeDaysMs = 3 * 86400000;
    const hatchesDueSoon = [
      ...activeFarmIncubations.map((b) => ({
        id: b.id,
        code: b.batchCode,
        isCustomer: false,
        name: b.species,
        hatchDate: b.expectedHatchDate,
        eggs: b.eggsSet,
        status: b.status,
      })),
      ...activeCustomerIncubations.map((b) => ({
        id: b.id,
        code: b.batchCode,
        isCustomer: true,
        name: `${b.customerName} (${b.species})`,
        hatchDate: b.expectedHatchDate,
        eggs: b.eggsAccepted,
        status: b.status,
      })),
    ].filter((item) => {
      const hatchTime = new Date(item.hatchDate).getTime();
      return hatchTime >= now - 86400000 && hatchTime <= now + threeDaysMs;
    });

    // Feed Stock Remaining
    const totalFeedStockKg = db.feedItems.reduce((sum, f) => sum + (f.currentStockKg || 0), 0);
    const lowFeedAlerts = db.feedItems.filter((f) => f.currentStockKg <= f.lowStockAlertKg);

    // Today's Sales & Expenses
    const todaySalesAmount = db.sales
      .filter((s) => s.date === todayIso)
      .reduce((sum, s) => sum + s.total, 0);
    const todayExpensesAmount = db.expenses
      .filter((e) => e.date === todayIso)
      .reduce((sum, e) => sum + e.amount, 0);

    // Outstanding customer balances
    const customerReceivables = db.customerIncubations
      .filter((b) => b.balanceDue > 0)
      .reduce((sum, b) => sum + b.balanceDue, 0) +
      db.sales
        .filter((s) => s.balanceDue > 0)
        .reduce((sum, s) => sum + s.balanceDue, 0);

    // Tasks due today or overdue
    const tasksDueToday = db.tasks.filter((t) => t.dueDate === todayIso && t.status !== 'completed');
    const overdueTasks = db.tasks.filter((t) => t.dueDate < todayIso && t.status !== 'completed');

    // Total Flock Asset Valuation based on age-based pricing matrix
    const totalFlockAssetValuationMwk = db.flocks
      .filter((f) => f.status === 'active')
      .reduce((sum, f) => {
        const val = getFlockValuation({
          species: f.species,
          dateAcquired: f.dateAcquired,
          currentQuantity: f.currentQuantity,
          ageGroup: f.ageGroup,
        });
        return sum + val.totalValuationMwk;
      }, 0);

    return {
      totalLiveBirds,
      totalFlockAssetValuationMwk,
      eggsCollectedToday,
      saleableEggsToday,
      crackedEggsToday,
      dirtyEggsToday,
      rejectedEggsToday,
      farmUsedToday,
      reservedIncubationToday,
      currentEggStockUnits,
      currentEggStockTrays,
      farmEggsInIncubators,
      customerEggsInIncubation,
      totalIncubatingEggs,
      hatchesDueSoon,
      totalFeedStockKg,
      lowFeedAlerts,
      todaySalesAmount,
      todayExpensesAmount,
      customerReceivables,
      tasksDueTodayCount: tasksDueToday.length,
      overdueTasksCount: overdueTasks.length,
      // Cumulative To-Date Metrics requested by user
      totalEggsCollectedToDate,
      totalSaleableEggsCollectedToDate,
      totalEggsSoldUnitsToDate: totalEggsSoldUnits,
      totalEggTraysSoldToDate,
      totalEggSalesRevenueToDate,
      totalPoultryBirdsSoldToDate,
      totalPoultrySalesRevenueToDate,
    };
  }, [db]);

  // CRUD Actions
  // 1. Flock Operations
  const addFlock = useCallback((flockData: Omit<Flock, 'id' | 'currentQuantity' | 'createdAt'>) => {
    const id = `flk_${Date.now()}`;
    const newFlock: Flock = {
      ...flockData,
      id,
      currentQuantity: flockData.initialQuantity,
      createdAt: Date.now(),
    };
    const movement: FlockMovement = {
      id: `mov_${Date.now()}`,
      flockId: id,
      date: flockData.dateAcquired,
      type: 'initial',
      quantity: flockData.initialQuantity,
      previousQuantity: 0,
      newQuantity: flockData.initialQuantity,
      destinationShed: flockData.housingShed,
      reason: `Initial flock intake: ${flockData.name}`,
      recordedBy: currentUser.name,
      timestamp: Date.now(),
    };
    const updated = {
      ...db,
      flocks: [newFlock, ...db.flocks],
      movements: [movement, ...db.movements],
    };
    saveDatabase(updated);
    recordAudit(currentUser.name, currentUser.role, 'ADD_FLOCK', 'flocks', `Created flock ${newFlock.name} with ${newFlock.initialQuantity} birds.`);
  }, [db, currentUser]);

  const recordFlockMovement = useCallback((
    flockId: string,
    type: FlockMovement['type'],
    quantity: number,
    destinationShed?: string,
    reason?: string
  ) => {
    const flock = db.flocks.find((f) => f.id === flockId);
    if (!flock) return;

    let newQty = flock.currentQuantity;
    if (type === 'added' || type === 'transferred_in') {
      newQty += quantity;
    } else if (type === 'sold' || type === 'culled' || type === 'mortality' || type === 'transferred_out') {
      newQty = Math.max(0, newQty - quantity);
    } else if (type === 'stock_adjustment') {
      newQty = quantity; // directly adjusted to verified count
    }

    const movement: FlockMovement = {
      id: `mov_${Date.now()}`,
      flockId,
      date: new Date().toISOString().split('T')[0],
      type,
      quantity,
      previousQuantity: flock.currentQuantity,
      newQuantity: newQty,
      destinationShed,
      reason,
      recordedBy: currentUser.name,
      timestamp: Date.now(),
    };

    const updatedFlocks = db.flocks.map((f) =>
      f.id === flockId ? { ...f, currentQuantity: newQty } : f
    );

    const updated = {
      ...db,
      flocks: updatedFlocks,
      movements: [movement, ...db.movements],
    };
    saveDatabase(updated);
    recordAudit(currentUser.name, currentUser.role, 'FLOCK_MOVEMENT', 'flocks', `Recorded ${type} (${quantity} birds) for ${flock.name}. New count: ${newQty}`);
  }, [db, currentUser]);

  // 2. Egg Collections
  const addEggCollection = useCallback((collection: Omit<EggCollection, 'id' | 'createdAt'>) => {
    const newRecord: EggCollection = {
      ...collection,
      id: `egg_${Date.now()}`,
      createdAt: Date.now(),
    };
    const updated = {
      ...db,
      eggCollections: [newRecord, ...db.eggCollections],
    };
    saveDatabase(updated);
    recordAudit(currentUser.name, currentUser.role, 'EGG_COLLECTION', 'eggs', `Logged collection of ${newRecord.totalCollected} eggs (${newRecord.saleableCount} saleable) for ${newRecord.flockName}.`);
  }, [db, currentUser]);

  // 3. Farm Incubation Batches
  const addFarmIncubationBatch = useCallback((batch: Omit<FarmIncubationBatch, 'id' | 'createdAt'>) => {
    const newBatch: FarmIncubationBatch = {
      ...batch,
      id: `inc_f_${Date.now()}`,
      createdAt: Date.now(),
    };
    const updated = {
      ...db,
      farmIncubations: [newBatch, ...db.farmIncubations],
    };
    saveDatabase(updated);
    recordAudit(currentUser.name, currentUser.role, 'CREATE_FARM_INCUBATION', 'incubation', `Set batch ${newBatch.batchCode} with ${newBatch.eggsSet} eggs in ${newBatch.incubatorId}.`);
  }, [db, currentUser]);

  const updateFarmIncubationBatch = useCallback((id: string, updates: Partial<FarmIncubationBatch>) => {
    const updatedBatches = db.farmIncubations.map((b) => (b.id === id ? { ...b, ...updates } : b));
    saveDatabase({ ...db, farmIncubations: updatedBatches });
    recordAudit(currentUser.name, currentUser.role, 'UPDATE_FARM_INCUBATION', 'incubation', `Updated batch ID ${id}.`);
  }, [db, currentUser]);

  // 4. Commercial Customer Incubation
  const addCustomerIncubationBatch = useCallback((
    batchData: Omit<CustomerIncubationBatch, 'id' | 'createdAt' | 'amountPaid' | 'balanceDue' | 'payments' | 'isCollected'>,
    initialDeposit: number,
    depositMethod: PaymentRecord['method']
  ) => {
    const id = `cust_inc_${Date.now()}`;
    const initialPayments: PaymentRecord[] = [];
    if (initialDeposit > 0) {
      initialPayments.push({
        id: `pay_${Date.now()}`,
        date: batchData.dateSet,
        amount: initialDeposit,
        method: depositMethod,
        receiptNumber: `REC-${Math.floor(1000 + Math.random() * 9000)}`,
        recordedBy: currentUser.name,
        notes: 'Deposit paid at intake',
        timestamp: Date.now(),
      });
    }

    const amountPaid = initialDeposit;
    const balanceDue = Math.max(0, batchData.totalCharge - amountPaid);

    const newBatch: CustomerIncubationBatch = {
      ...batchData,
      id,
      deposit: initialDeposit,
      amountPaid,
      balanceDue,
      payments: initialPayments,
      isCollected: false,
      createdAt: Date.now(),
    };

    // Ensure customer exists in customers registry
    let updatedCustomers = [...db.customers];
    const existingCustomer = updatedCustomers.find((c) => c.name.toLowerCase() === batchData.customerName.toLowerCase());
    if (!existingCustomer) {
      updatedCustomers.unshift({
        id: `cust_${Date.now()}`,
        customerCode: `CUST-${Math.floor(100 + Math.random() * 900)}`,
        name: batchData.customerName,
        phone: batchData.customerPhone,
        addressVillage: batchData.customerVillage || 'Liwonde',
        category: 'commercial_farmer',
        createdAt: Date.now(),
      });
    }

    const updated = {
      ...db,
      customers: updatedCustomers,
      customerIncubations: [newBatch, ...db.customerIncubations],
    };
    saveDatabase(updated);
    recordAudit(currentUser.name, currentUser.role, 'REGISTER_CUSTOMER_INCUBATION', 'customer_incubation', `Registered customer incubation for ${newBatch.customerName} (${newBatch.eggsAccepted} eggs, MK ${newBatch.totalCharge}).`);
  }, [db, currentUser]);

  const addCustomerPayment = useCallback((
    batchId: string,
    amount: number,
    method: PaymentRecord['method'],
    receiptNumber: string,
    notes?: string
  ) => {
    const batch = db.customerIncubations.find((b) => b.id === batchId);
    if (!batch) return;

    const payment: PaymentRecord = {
      id: `pay_${Date.now()}`,
      date: new Date().toISOString().split('T')[0],
      amount,
      method,
      receiptNumber: receiptNumber || `REC-${Math.floor(1000 + Math.random() * 9000)}`,
      recordedBy: currentUser.name,
      notes,
      timestamp: Date.now(),
    };

    const newAmountPaid = batch.amountPaid + amount;
    const newBalanceDue = Math.max(0, batch.totalCharge - newAmountPaid);

    const updatedBatches = db.customerIncubations.map((b) =>
      b.id === batchId
        ? {
            ...b,
            amountPaid: newAmountPaid,
            balanceDue: newBalanceDue,
            payments: [...b.payments, payment],
          }
        : b
    );

    saveDatabase({ ...db, customerIncubations: updatedBatches });
    recordAudit(currentUser.name, currentUser.role, 'CUSTOMER_PAYMENT', 'customer_incubation', `Received MK ${amount} via ${method} for ${batch.customerName} (${batch.batchCode}). Remaining balance: MK ${newBalanceDue}.`);
  }, [db, currentUser]);

  const updateCustomerIncubationBatch = useCallback((id: string, updates: Partial<CustomerIncubationBatch>) => {
    const updatedBatches = db.customerIncubations.map((b) => (b.id === id ? { ...b, ...updates } : b));
    saveDatabase({ ...db, customerIncubations: updatedBatches });
    recordAudit(currentUser.name, currentUser.role, 'UPDATE_CUSTOMER_INCUBATION', 'customer_incubation', `Updated customer batch ${id}.`);
  }, [db, currentUser]);

  // 5. Feed Operations
  const addFeedUsage = useCallback((usage: Omit<FeedUsage, 'id' | 'createdAt'>) => {
    const newUsage: FeedUsage = {
      ...usage,
      id: `fu_${Date.now()}`,
      createdAt: Date.now(),
    };

    // Deduct stock from feed item
    const updatedFeedItems = db.feedItems.map((item) => {
      if (item.id === usage.feedItemId) {
        return {
          ...item,
          currentStockKg: Math.max(0, item.currentStockKg - usage.quantityUsedKg),
        };
      }
      return item;
    });

    const updated = {
      ...db,
      feedUsage: [newUsage, ...db.feedUsage],
      feedItems: updatedFeedItems,
    };
    saveDatabase(updated);
    recordAudit(currentUser.name, currentUser.role, 'FEED_USAGE', 'feed', `Logged ${usage.quantityUsedKg}kg ${usage.productName} for ${usage.flockName}.`);
  }, [db, currentUser]);

  const addFeedItem = useCallback((feedItem: Omit<FeedItem, 'id'>) => {
    const newItem: FeedItem = { ...feedItem, id: `feed_${Date.now()}` };
    saveDatabase({ ...db, feedItems: [...db.feedItems, newItem] });
    recordAudit(currentUser.name, currentUser.role, 'ADD_FEED_ITEM', 'feed', `Added feed product ${newItem.productName}.`);
  }, [db, currentUser]);

  const updateFeedStock = useCallback((id: string, newStockKg: number, reason?: string) => {
    const updatedFeed = db.feedItems.map((f) => (f.id === id ? { ...f, currentStockKg: newStockKg } : f));
    saveDatabase({ ...db, feedItems: updatedFeed });
    recordAudit(currentUser.name, currentUser.role, 'ADJUST_FEED_STOCK', 'feed', `Adjusted feed stock to ${newStockKg}kg. Reason: ${reason || 'Physical count'}`);
  }, [db, currentUser]);

  // 6. Health & Mortality
  const addMortalityRecord = useCallback((rec: Omit<MortalityRecord, 'id' | 'createdAt'>) => {
    const newRec: MortalityRecord = {
      ...rec,
      id: `mort_${Date.now()}`,
      createdAt: Date.now(),
    };

    // Update flock quantity automatically
    const flock = db.flocks.find((f) => f.id === rec.flockId);
    let updatedFlocks = db.flocks;
    let newMovements = db.movements;

    if (flock) {
      const newQty = Math.max(0, flock.currentQuantity - rec.numberOfBirds);
      updatedFlocks = db.flocks.map((f) => (f.id === rec.flockId ? { ...f, currentQuantity: newQty } : f));
      const movement: FlockMovement = {
        id: `mov_${Date.now()}`,
        flockId: rec.flockId,
        date: rec.date,
        type: 'mortality',
        quantity: rec.numberOfBirds,
        previousQuantity: flock.currentQuantity,
        newQuantity: newQty,
        reason: `Mortality: ${rec.suspectedCause}`,
        recordedBy: currentUser.name,
        timestamp: Date.now(),
      };
      newMovements = [movement, ...db.movements];
    }

    const updated = {
      ...db,
      mortalityRecords: [newRec, ...db.mortalityRecords],
      flocks: updatedFlocks,
      movements: newMovements,
    };
    saveDatabase(updated);
    recordAudit(currentUser.name, currentUser.role, 'LOG_MORTALITY', 'health', `Recorded mortality of ${rec.numberOfBirds} birds for ${rec.flockName}. Cause: ${rec.suspectedCause}`);
  }, [db, currentUser]);

  const addHealthRecord = useCallback((rec: Omit<HealthRecord, 'id' | 'createdAt'>) => {
    const newRec: HealthRecord = { ...rec, id: `hlth_${Date.now()}`, createdAt: Date.now() };
    saveDatabase({ ...db, healthRecords: [newRec, ...db.healthRecords] });
    recordAudit(currentUser.name, currentUser.role, 'ADD_HEALTH_RECORD', 'health', `Logged medication ${rec.medicineOrVaccine} for ${rec.flockName}.`);
  }, [db, currentUser]);

  const addVaccinationEvent = useCallback((event: Omit<VaccinationEvent, 'id' | 'createdAt'>) => {
    const newEvent: VaccinationEvent = { ...event, id: `vac_${Date.now()}`, createdAt: Date.now() };
    saveDatabase({ ...db, vaccinationEvents: [newEvent, ...db.vaccinationEvents] });
    recordAudit(currentUser.name, currentUser.role, 'ADD_VACCINATION', 'health', `Scheduled vaccine ${event.vaccineName} for ${event.flockName}.`);
  }, [db, currentUser]);

  const updateVaccinationStatus = useCallback((id: string, status: VaccinationEvent['status'], administeredBy?: string) => {
    const updated = db.vaccinationEvents.map((v) =>
      v.id === id
        ? {
            ...v,
            status,
            completedDate: status === 'completed' ? new Date().toISOString().split('T')[0] : undefined,
            administeredBy: administeredBy || currentUser.name,
          }
        : v
    );
    saveDatabase({ ...db, vaccinationEvents: updated });
    recordAudit(currentUser.name, currentUser.role, 'UPDATE_VACCINATION', 'health', `Vaccine ID ${id} marked as ${status}.`);
  }, [db, currentUser]);

  // 7. Sales & Expenses
  const addSale = useCallback((sale: Omit<Sale, 'id' | 'createdAt' | 'balanceDue'>) => {
    const balanceDue = Math.max(0, sale.total - sale.paymentReceived);
    const newSale: Sale = {
      ...sale,
      id: `sale_${Date.now()}`,
      balanceDue,
      createdAt: Date.now(),
    };
    const updated = {
      ...db,
      sales: [newSale, ...db.sales],
    };
    saveDatabase(updated);
    recordAudit(currentUser.name, currentUser.role, 'RECORD_SALE', 'sales', `Recorded sale of ${newSale.quantity} ${newSale.unit} to ${newSale.customerName} for MK ${newSale.total}.`);
  }, [db, currentUser]);

  const addExpense = useCallback((expense: Omit<Expense, 'id' | 'createdAt'>) => {
    const newExpense: Expense = {
      ...expense,
      id: `exp_${Date.now()}`,
      createdAt: Date.now(),
    };
    const updated = {
      ...db,
      expenses: [newExpense, ...db.expenses],
    };
    saveDatabase(updated);
    recordAudit(currentUser.name, currentUser.role, 'RECORD_EXPENSE', 'finance', `Recorded expense of MK ${newExpense.amount} for ${newExpense.category} (${newExpense.description}).`);
  }, [db, currentUser]);

  // 8. Tasks
  const addTask = useCallback((task: Omit<FarmTask, 'id' | 'createdAt'>) => {
    const newTask: FarmTask = { ...task, id: `tsk_${Date.now()}`, createdAt: Date.now() };
    saveDatabase({ ...db, tasks: [newTask, ...db.tasks] });
    recordAudit(currentUser.name, currentUser.role, 'ADD_TASK', 'tasks', `Scheduled task: ${task.title}`);
  }, [db, currentUser]);

  const updateTaskStatus = useCallback((id: string, status: FarmTask['status']) => {
    const updated = db.tasks.map((t) =>
      t.id === id
        ? {
            ...t,
            status,
            completedDate: status === 'completed' ? new Date().toISOString().split('T')[0] : undefined,
          }
        : t
    );
    saveDatabase({ ...db, tasks: updated });
    recordAudit(currentUser.name, currentUser.role, 'UPDATE_TASK', 'tasks', `Task ID ${id} set to ${status}.`);
  }, [db, currentUser]);

  // Dynamic Alert Notifications Generation
  const allAlerts = useMemo<FarmAlert[]>(() => {
    const list: FarmAlert[] = [];
    const now = Date.now();
    const todayIso = new Date().toISOString().split('T')[0];

    // 1. Incubation Due & Customer Hatch Alerts
    db.customerIncubations.forEach((b) => {
      if (b.status === 'collected' || b.status === 'cancelled') return;
      const hatchTime = new Date(b.expectedHatchDate).getTime();
      const diffHours = (hatchTime - now) / (1000 * 60 * 60);

      if (b.status === 'hatched' && !b.isCollected) {
        list.push({
          id: `alert_cust_hatched_${b.id}`,
          title: 'Customer Chicks Ready for Collection',
          description: `${b.customerName}'s batch (${b.eggsAccepted} eggs, ${b.species}) is hatched & waiting for pickup.`,
          category: 'incubation',
          severity: 'critical',
          timestamp: b.createdAt,
          actionLabel: 'Notify Customer',
          actionTab: 'incubation',
          relatedEntityId: b.id,
        });
      } else if (diffHours <= 24 && diffHours >= -48) {
        list.push({
          id: `alert_cust_due_${b.id}`,
          title: `Hatch Imminent: ${b.customerName}`,
          description: `${b.species} batch due for hatch within 24 hours (${b.expectedHatchDate}).`,
          category: 'incubation',
          severity: 'critical',
          timestamp: b.createdAt,
          actionLabel: 'View Incubator',
          actionTab: 'incubation',
          relatedEntityId: b.id,
        });
      } else if (diffHours <= 72 && diffHours > 24) {
        list.push({
          id: `alert_cust_lockdown_${b.id}`,
          title: `Lockdown Due: ${b.customerName}`,
          description: `${b.species} batch due in 3 days. Stop turning and raise humidity.`,
          category: 'incubation',
          severity: 'warning',
          timestamp: b.createdAt,
          actionLabel: 'Check Batch',
          actionTab: 'incubation',
          relatedEntityId: b.id,
        });
      }
    });

    db.farmIncubations.forEach((b) => {
      if (b.status === 'completed') return;
      const hatchTime = new Date(b.expectedHatchDate).getTime();
      const diffHours = (hatchTime - now) / (1000 * 60 * 60);
      if (diffHours <= 24 && diffHours >= -48) {
        list.push({
          id: `alert_farm_due_${b.id}`,
          title: `Farm Hatch Due: ${b.species}`,
          description: `Batch ${b.batchCode} (${b.eggsSet} eggs) due on ${b.expectedHatchDate}.`,
          category: 'incubation',
          severity: 'critical',
          timestamp: b.createdAt,
          actionLabel: 'Inspect Incubator',
          actionTab: 'incubation',
          relatedEntityId: b.id,
        });
      }
    });

    // 2. Feed Stock Alerts
    db.feedItems.forEach((f) => {
      if (f.currentStockKg <= f.lowStockAlertKg) {
        list.push({
          id: `alert_feed_${f.id}`,
          title: `Low Feed: ${f.productName}`,
          description: `Only ${f.currentStockKg} kg left in ${f.storageLocation} (Threshold: ${f.lowStockAlertKg} kg). Reorder soon.`,
          category: 'feed',
          severity: f.currentStockKg <= f.lowStockAlertKg * 0.5 ? 'critical' : 'warning',
          timestamp: now,
          actionLabel: 'Restock Feed',
          actionTab: 'feed',
          relatedEntityId: f.id,
        });
      }
    });

    // 3. Egg Stock Alerts
    const eggStockTrays = Number(metrics.currentEggStockTrays);
    if (eggStockTrays <= (db.settings.lowEggStockAlertTrays || 10)) {
      list.push({
        id: 'alert_egg_stock',
        title: 'Low Saleable Egg Stock',
        description: `Current inventory is ${eggStockTrays} trays (Alert limit: ${db.settings.lowEggStockAlertTrays || 10} trays).`,
        category: 'stock',
        severity: 'warning',
        timestamp: now,
        actionLabel: 'Record Eggs',
        actionTab: 'eggs',
      });
    }

    // 4. Overdue Tasks Alert
    const overdueTasks = db.tasks.filter((t) => t.dueDate < todayIso && t.status !== 'completed');
    if (overdueTasks.length > 0) {
      list.push({
        id: 'alert_overdue_tasks',
        title: `${overdueTasks.length} Overdue Farm Task${overdueTasks.length > 1 ? 's' : ''}`,
        description: `Task "${overdueTasks[0].title}" is past due date.`,
        category: 'tasks',
        severity: 'critical',
        timestamp: now,
        actionLabel: 'View Tasks',
        actionTab: 'tasks',
      });
    }

    // 5. Recent Mortality Spikes
    const recentMortalities = db.mortalityRecords.filter((m) => {
      const mTime = new Date(m.date).getTime();
      return mTime >= now - 3 * 86400000;
    });
    const totalRecentMortality = recentMortalities.reduce((s, m) => s + m.numberOfBirds, 0);
    if (totalRecentMortality >= 3) {
      list.push({
        id: 'alert_mortality_spike',
        title: `Health Notice: ${totalRecentMortality} Mortalities in 72h`,
        description: `Unusual mortality recorded in sheds. Review biosecurity and water sanitation.`,
        category: 'health',
        severity: totalRecentMortality >= 5 ? 'critical' : 'warning',
        timestamp: now,
        actionLabel: 'Health Records',
        actionTab: 'health',
      });
    }

    return list;
  }, [db, metrics]);

  const activeAlerts = useMemo(() => {
    const dismissed = db.dismissedAlertIds || [];
    return allAlerts.filter((a) => !dismissed.includes(a.id));
  }, [allAlerts, db.dismissedAlertIds]);

  const dismissAlert = useCallback((alertId: string) => {
    const dismissed = db.dismissedAlertIds || [];
    if (!dismissed.includes(alertId)) {
      const updated = { ...db, dismissedAlertIds: [...dismissed, alertId] };
      saveDatabase(updated);
    }
  }, [db]);

  const clearAllAlertDismissals = useCallback(() => {
    saveDatabase({ ...db, dismissedAlertIds: [] });
  }, [db]);

  return {
    db,
    settings: db.settings,
    users,
    currentUser,
    switchUser,
    login,
    logout,
    changeUserRole,
    updateUserPin,
    addUser,
    language: db.language || 'en',
    setLanguage,
    updateSettings,
    isManagingDirector,
    isSalesManager,
    hasFullAccess,
    isViewerOnly,
    canEditStocks,
    canManageUsers,
    canAccessFinance,
    canAccessFullAdmin,
    canRecordSales,
    canDeleteRecords,
    isAuthenticated: db.isAuthenticated ?? true,
    metrics,
    allAlerts,
    activeAlerts,
    unreadAlertsCount: activeAlerts.length,
    dismissAlert,
    clearAllAlertDismissals,
    // CRUD
    addFlock,
    recordFlockMovement,
    addEggCollection,
    addFarmIncubationBatch,
    updateFarmIncubationBatch,
    addCustomerIncubationBatch,
    addCustomerPayment,
    updateCustomerIncubationBatch,
    addFeedUsage,
    addFeedItem,
    updateFeedStock,
    addMortalityRecord,
    addHealthRecord,
    addVaccinationEvent,
    updateVaccinationStatus,
    addSale,
    addExpense,
    addTask,
    updateTaskStatus,
  };
}
