<template>
  <div class="period-calculator">
    <div class="calculator-header">
      <h3>Period Calculator</h3>
      <button class="close-button" @click="$emit('close')">
        <i class="fas fa-times"></i>
      </button>
    </div>
    
    <div class="calculator-content">
      <div class="form-section">
        <h4>Enter Your Cycle Information</h4>
        
        <div class="form-group">
          <label for="last-period">First day of your last period</label>
          <input 
            type="date" 
            id="last-period" 
            v-model="lastPeriodDate"
            @change="calculateCycle"
          >
        </div>
        
        <div class="form-group">
          <label for="cycle-length">Average cycle length (days)</label>
          <div class="input-with-controls">
            <button @click="decrementCycleLength" :disabled="cycleLength <= 21">
              <i class="fas fa-minus"></i>
            </button>
            <input 
              type="number" 
              id="cycle-length" 
              v-model.number="cycleLength"
              min="21" 
              max="35"
              @change="calculateCycle"
            >
            <button @click="incrementCycleLength" :disabled="cycleLength >= 35">
              <i class="fas fa-plus"></i>
            </button>
          </div>
          <div class="range-info">
            <span>21</span>
            <input 
              type="range" 
              min="21" 
              max="35" 
              v-model.number="cycleLength"
              @input="calculateCycle"
            >
            <span>35</span>
          </div>
        </div>
        
        <div class="form-group">
          <label for="period-length">Average period length (days)</label>
          <div class="input-with-controls">
            <button @click="decrementPeriodLength" :disabled="periodLength <= 3">
              <i class="fas fa-minus"></i>
            </button>
            <input 
              type="number" 
              id="period-length" 
              v-model.number="periodLength"
              min="3" 
              max="7"
              @change="calculateCycle"
            >
            <button @click="incrementPeriodLength" :disabled="periodLength >= 7">
              <i class="fas fa-plus"></i>
            </button>
          </div>
          <div class="range-info">
            <span>3</span>
            <input 
              type="range" 
              min="3" 
              max="7" 
              v-model.number="periodLength"
              @input="calculateCycle"
            >
            <span>7</span>
          </div>
        </div>
      </div>
      
      <div class="results-section" v-if="hasCalculated">
        <h4>Your Cycle Forecast</h4>
        
        <div class="cycle-timeline">
          <div class="timeline-item period">
            <div class="timeline-icon">
              <i class="fas fa-tint"></i>
            </div>
            <div class="timeline-content">
              <h5>Period</h5>
              <p>{{ formatDateRange(nextPeriodStart, nextPeriodEnd) }}</p>
              <div class="days-indicator">
                <span class="days-count">{{ getDaysUntil(nextPeriodStart) }}</span>
                <span class="days-label">days away</span>
              </div>
            </div>
          </div>
          
          <div class="timeline-item fertile">
            <div class="timeline-icon">
              <i class="fas fa-seedling"></i>
            </div>
            <div class="timeline-content">
              <h5>Fertile Window</h5>
              <p>{{ formatDateRange(fertileWindowStart, fertileWindowEnd) }}</p>
              <div class="days-indicator" v-if="getDaysUntil(fertileWindowStart) > 0">
                <span class="days-count">{{ getDaysUntil(fertileWindowStart) }}</span>
                <span class="days-label">days away</span>
              </div>
              <div class="days-indicator current" v-else-if="isCurrentlyInFertileWindow">
                <span class="days-label">Currently in fertile window</span>
              </div>
            </div>
          </div>
          
          <div class="timeline-item ovulation">
            <div class="timeline-icon">
              <i class="fas fa-egg"></i>
            </div>
            <div class="timeline-content">
              <h5>Ovulation Day</h5>
              <p>{{ formatDate(ovulationDate) }}</p>
              <div class="days-indicator" v-if="getDaysUntil(ovulationDate) > 0">
                <span class="days-count">{{ getDaysUntil(ovulationDate) }}</span>
                <span class="days-label">days away</span>
              </div>
              <div class="days-indicator current" v-else-if="isOvulationToday">
                <span class="days-label">Today</span>
              </div>
            </div>
          </div>
        </div>
        
        <div class="next-cycles">
          <h5>Upcoming Cycles</h5>
          <div class="cycles-list">
            <div class="cycle-item" v-for="(cycle, index) in upcomingCycles" :key="index">
              <div class="cycle-date">
                <i class="fas fa-calendar-alt"></i>
                <span>{{ formatDateRange(cycle.start, cycle.end) }}</span>
              </div>
              <div class="cycle-days">
                <span>{{ getDaysUntil(cycle.start) }} days away</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="placeholder-message" v-else>
        <div class="placeholder-icon">
          <i class="fas fa-calendar-alt"></i>
        </div>
        <p>Enter your cycle information to see predictions</p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PeriodCalculator',
  
  data() {
    return {
      lastPeriodDate: this.formatDateForInput(new Date()),
      cycleLength: 28,
      periodLength: 5,
      hasCalculated: false,
      nextPeriodStart: null,
      nextPeriodEnd: null,
      fertileWindowStart: null,
      fertileWindowEnd: null,
      ovulationDate: null,
      upcomingCycles: []
    };
  },
  
  computed: {
    isCurrentlyInFertileWindow() {
      if (!this.fertileWindowStart || !this.fertileWindowEnd) return false;
      
      const today = new Date();
      return today >= this.fertileWindowStart && today <= this.fertileWindowEnd;
    },
    
    isOvulationToday() {
      if (!this.ovulationDate) return false;
      
      const today = new Date();
      return this.isSameDay(today, this.ovulationDate);
    }
  },
  
  methods: {
    calculateCycle() {
      if (!this.lastPeriodDate) return;
      
      const lastPeriod = new Date(this.lastPeriodDate);
      
      // Calculate next period start date
      this.nextPeriodStart = new Date(lastPeriod);
      this.nextPeriodStart.setDate(lastPeriod.getDate() + this.cycleLength);
      
      // Calculate next period end date
      this.nextPeriodEnd = new Date(this.nextPeriodStart);
      this.nextPeriodEnd.setDate(this.nextPeriodStart.getDate() + this.periodLength - 1);
      
      // Calculate ovulation date (typically 14 days before next period)
      this.ovulationDate = new Date(this.nextPeriodStart);
      this.ovulationDate.setDate(this.nextPeriodStart.getDate() - 14);
      
      // Calculate fertile window (typically 5 days before ovulation plus ovulation day)
      this.fertileWindowStart = new Date(this.ovulationDate);
      this.fertileWindowStart.setDate(this.ovulationDate.getDate() - 5);
      
      this.fertileWindowEnd = new Date(this.ovulationDate);
      
      // Calculate upcoming cycles (next 3)
      this.upcomingCycles = [];
      let cycleStart = new Date(this.nextPeriodStart);
      
      for (let i = 0; i < 3; i++) {
        const cycleEnd = new Date(cycleStart);
        cycleEnd.setDate(cycleStart.getDate() + this.periodLength - 1);
        
        this.upcomingCycles.push({
          start: new Date(cycleStart),
          end: new Date(cycleEnd)
        });
        
        // Move to next cycle
        cycleStart.setDate(cycleStart.getDate() + this.cycleLength);
      }
      
      this.hasCalculated = true;
    },
    
    formatDateForInput(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    },
    
    formatDate(date) {
      if (!date) return '';
      
      const options = { weekday: 'short', month: 'short', day: 'numeric' };
      return date.toLocaleDateString('en-US', options);
    },
    
    formatDateRange(startDate, endDate) {
      if (!startDate || !endDate) return '';
      
      const startOptions = { month: 'short', day: 'numeric' };
      const endOptions = { month: 'short', day: 'numeric' };
      
      return `${startDate.toLocaleDateString('en-US', startOptions)} - ${endDate.toLocaleDateString('en-US', endOptions)}`;
    },
    
    getDaysUntil(date) {
      if (!date) return 0;
      
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      
      const targetDate = new Date(date);
      targetDate.setHours(0, 0, 0, 0);
      
      const timeDiff = targetDate - today;
      const daysDiff = Math.ceil(timeDiff / (1000 * 60 * 60 * 24));
      
      return daysDiff > 0 ? daysDiff : 0;
    },
    
    isSameDay(date1, date2) {
      return date1.getDate() === date2.getDate() &&
             date1.getMonth() === date2.getMonth() &&
             date1.getFullYear() === date2.getFullYear();
    },
    
    incrementCycleLength() {
      if (this.cycleLength < 35) {
        this.cycleLength++;
        this.calculateCycle();
      }
    },
    
    decrementCycleLength() {
      if (this.cycleLength > 21) {
        this.cycleLength--;
        this.calculateCycle();
      }
    },
    
    incrementPeriodLength() {
      if (this.periodLength < 7) {
        this.periodLength++;
        this.calculateCycle();
      }
    },
    
    decrementPeriodLength() {
      if (this.periodLength > 3) {
        this.periodLength--;
        this.calculateCycle();
      }
    }
  }
};
</script>

<style scoped>
.period-calculator {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: #f8f9fa;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
  font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
}

.calculator-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  background: linear-gradient(135deg, #e91e63, #c2185b);
  color: white;
}

.calculator-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.close-button {
  background: none;
  border: none;
  color: white;
  font-size: 18px;
  cursor: pointer;
  opacity: 0.8;
  transition: opacity 0.2s;
}

.close-button:hover {
  opacity: 1;
}

.calculator-content {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
}

.form-section, .results-section {
  background: white;
  border-radius: 10px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

h4 {
  margin-top: 0;
  margin-bottom: 20px;
  color: #333;
  font-weight: 600;
  font-size: 16px;
}

.form-group {
  margin-bottom: 20px;
}

label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #555;
}

input[type="date"], input[type="number"] {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  transition: border-color 0.3s;
}

input[type="date"]:focus, input[type="number"]:focus {
  border-color: #e91e63;
  outline: none;
}

.input-with-controls {
  display: flex;
  align-items: center;
}

.input-with-controls button {
  width: 36px;
  height: 36px;
  border: 1px solid #ddd;
  background: #f5f5f5;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.3s;
}

.input-with-controls button:first-child {
  border-radius: 6px 0 0 6px;
}

.input-with-controls button:last-child {
  border-radius: 0 6px 6px 0;
}

.input-with-controls button:hover {
  background: #e0e0e0;
}

.input-with-controls button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.input-with-controls input {
  width: 60px;
  text-align: center;
  border-radius: 0;
  border-left: none;
  border-right: none;
}

.range-info {
  display: flex;
  align-items: center;
  margin-top: 10px;
  font-size: 12px;
  color: #777;
}

.range-info span {
  width: 20px;
  text-align: center;
}

.range-info input[type="range"] {
  flex: 1;
  margin: 0 10px;
}

.cycle-timeline {
  display: flex;
  flex-direction: column;
  gap: 15px;
  margin-bottom: 25px;
}

.timeline-item {
  display: flex;
  align-items: flex-start;
  padding: 15px;
  border-radius: 8px;
  background: #f9f9f9;
  transition: transform 0.3s;
}

.timeline-item:hover {
  transform: translateY(-2px);
}

.timeline-item.period {
  border-left: 4px solid #e91e63;
}

.timeline-item.fertile {
  border-left: 4px solid #4caf50;
}

.timeline-item.ovulation {
  border-left: 4px solid #2196f3;
}

.timeline-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 15px;
  color: white;
  flex-shrink: 0;
}

.timeline-item.period .timeline-icon {
  background: #e91e63;
}

.timeline-item.fertile .timeline-icon {
  background: #4caf50;
}

.timeline-item.ovulation .timeline-icon {
  background: #2196f3;
}

.timeline-content {
  flex: 1;
}

.timeline-content h5 {
  margin: 0 0 5px 0;
  font-weight: 600;
  font-size: 15px;
}

.timeline-content p {
  margin: 0 0 10px 0;
  color: #555;
}

.days-indicator {
  display: flex;
  align-items: baseline;
  font-size: 14px;
}

.days-count {
  font-size: 18px;
  font-weight: 700;
  margin-right: 5px;
  color: #333;
}

.days-label {
  color: #777;
}

.days-indicator.current {
  color: #4caf50;
  font-weight: 500;
}

.next-cycles h5 {
  margin: 0 0 15px 0;
  font-weight: 600;
  font-size: 15px;
}

.cycles-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.cycle-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 15px;
  background: #f5f5f5;
  border-radius: 6px;
  transition: background 0.3s;
}

.cycle-item:hover {
  background: #eeeeee;
}

.cycle-date {
  display: flex;
  align-items: center;
}

.cycle-date i {
  margin-right: 10px;
  color: #e91e63;
}

.cycle-days {
  font-size: 13px;
  color: #777;
}

.placeholder-message {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  text-align: center;
  color: #999;
}

.placeholder-icon {
  font-size: 48px;
  margin-bottom: 15px;
  color: #ddd;
}

.placeholder-message p {
  margin: 0;
  font-size: 16px;
}
</style>
