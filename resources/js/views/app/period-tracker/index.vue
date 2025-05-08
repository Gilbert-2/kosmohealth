<template>
  <div class="period-tracker-wrapper">
    <base-container>
      <div class="row">
        <div class="col-12 col-lg-8">
          <card>
            <template slot="header">
              <h5 class="card-title">
                Period Cycles
                <base-button @click="showAddModal = true" type="button" variant="primary" class="btn-sm float-right">
                  <i class="fas fa-plus"></i> Add Period
                </base-button>
              </h5>
            </template>
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Duration</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="cycle in cycles" :key="cycle.id">
                    <td>{{ formatDate(cycle.start_date) }}</td>
                    <td>{{ formatDate(cycle.end_date) }}</td>
                    <td>{{ getDuration(cycle.start_date, cycle.end_date) }} days</td>
                    <td>
                      <a href="#" @click.prevent="deleteCycle(cycle.id)" class="text-danger">
                        <i class="fas fa-trash"></i>
                      </a>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </card>
        </div>

        <div class="col-12 col-lg-4">
          <card>
            <template slot="header">
              <h5 class="card-title">Predictions</h5>
            </template>
            <div v-if="predictions">
              <div class="mb-3">
                <label class="text-muted">Next Period Expected</label>
                <h4>{{ formatDate(predictions.next_period) }}</h4>
              </div>
              <div>
                <label class="text-muted">Fertile Window</label>
                <h4>{{ formatDate(predictions.fertile_window.start) }} - {{ formatDate(predictions.fertile_window.end) }}</h4>
              </div>
            </div>
            <div v-else class="text-muted">
              Not enough data for predictions
            </div>
          </card>

          <card class="mt-4">
            <template slot="header">
              <h5 class="card-title">
                Symptoms Log
                <base-button @click="showSymptomsModal = true" type="button" variant="primary" class="btn-sm float-right">
                  <i class="fas fa-plus"></i> Log Symptoms
                </base-button>
              </h5>
            </template>
            <div class="symptoms-list">
              <template v-if="symptoms.length">
                <div v-for="(group, date) in groupedSymptoms" :key="date" class="mb-3">
                  <label class="text-muted">{{ formatDate(date) }}</label>
                  <div class="symptoms-tags">
                    <span v-for="symptom in group" :key="symptom.id" class="badge badge-primary mr-2">
                      {{ symptom.symptom }}
                    </span>
                  </div>
                </div>
              </template>
              <div v-else class="text-muted">
                No symptoms logged
              </div>
            </div>
          </card>
        </div>
      </div>
    </base-container>

    <!-- Add Period Modal -->
    <modal v-model="showAddModal" @hidden="resetForm" title="Add Period">
      <form @submit.prevent="saveCycle">
        <div class="form-group">
          <label>Start Date</label>
          <date-picker v-model="form.start_date" :max-date="form.end_date || new Date()" class="form-control" />
        </div>
        <div class="form-group">
          <label>End Date</label>
          <date-picker v-model="form.end_date" :min-date="form.start_date" :max-date="new Date()" class="form-control" />
        </div>
        <div class="text-right">
          <base-button type="submit" variant="primary" :loading="isLoading">Save</base-button>
        </div>
      </form>
    </modal>

    <!-- Add Symptoms Modal -->
    <modal v-model="showSymptomsModal" @hidden="resetSymptomsForm" title="Log Symptoms">
      <form @submit.prevent="logSymptoms">
        <div class="form-group">
          <label>Date</label>
          <date-picker v-model="symptomsForm.date" :max-date="new Date()" class="form-control" />
        </div>
        <div class="form-group">
          <label>Symptoms</label>
          <multiselect
            v-model="symptomsForm.symptoms"
            :options="availableSymptoms"
            :multiple="true"
            :taggable="true"
            @tag="addSymptom"
            placeholder="Select or type symptoms"
          />
        </div>
        <div class="text-right">
          <base-button type="submit" variant="primary" :loading="isLoading">Save</base-button>
        </div>
      </form>
    </modal>
  </div>
</template>

<script>
export default {
  data() {
    return {
      cycles: [],
      symptoms: [],
      predictions: null,
      showAddModal: false,
      showSymptomsModal: false,
      isLoading: false,
      form: {
        start_date: null,
        end_date: null
      },
      symptomsForm: {
        date: new Date(),
        symptoms: []
      },
      availableSymptoms: [
        'Cramps', 'Headache', 'Bloating', 'Fatigue', 'Mood Swings',
        'Tender Breasts', 'Acne', 'Back Pain', 'Nausea'
      ]
    }
  },

  computed: {
    groupedSymptoms() {
      return this.symptoms.reduce((groups, symptom) => {
        const date = symptom.date
        if (!groups[date]) {
          groups[date] = []
        }
        groups[date].push(symptom)
        return groups
      }, {})
    }
  },

  mounted() {
    this.loadData()
  },

  methods: {
    async loadData() {
      await Promise.all([
        this.loadCycles(),
        this.loadPredictions(),
        this.loadSymptoms()
      ])
    },

    async loadCycles() {
      try {
        const response = await this.$http.get('/api/period-tracker/cycles')
        this.cycles = response.data.cycles
      } catch (error) {
        this.$toasted.error(error.response?.data?.message || 'Failed to load cycles')
      }
    },

    async loadPredictions() {
      try {
        const response = await this.$http.get('/api/period-tracker/predictions')
        this.predictions = response.data
      } catch (error) {
        this.$toasted.error(error.response?.data?.message || 'Failed to load predictions')
      }
    },

    async loadSymptoms() {
      try {
        const response = await this.$http.get('/api/period-tracker/symptoms')
        this.symptoms = response.data.symptoms
      } catch (error) {
        this.$toasted.error(error.response?.data?.message || 'Failed to load symptoms')
      }
    },

    async saveCycle() {
      if (!this.form.start_date || !this.form.end_date) {
        return this.$toasted.error('Please fill all fields')
      }

      this.isLoading = true
      try {
        await this.$http.post('/api/period-tracker/cycles', this.form)
        this.$toasted.success('Period cycle added successfully')
        this.showAddModal = false
        this.loadData()
      } catch (error) {
        this.$toasted.error(error.response?.data?.message || 'Failed to save cycle')
      } finally {
        this.isLoading = false
      }
    },

    async deleteCycle(id) {
      if (!confirm('Are you sure you want to delete this cycle?')) return

      try {
        await this.$http.delete(`/api/period-tracker/cycles/${id}`)
        this.$toasted.success('Period cycle deleted successfully')
        this.loadData()
      } catch (error) {
        this.$toasted.error(error.response?.data?.message || 'Failed to delete cycle')
      }
    },

    async logSymptoms() {
      if (!this.symptomsForm.date || !this.symptomsForm.symptoms.length) {
        return this.$toasted.error('Please fill all fields')
      }

      this.isLoading = true
      try {
        await this.$http.post('/api/period-tracker/symptoms', this.symptomsForm)
        this.$toasted.success('Symptoms logged successfully')
        this.showSymptomsModal = false
        this.loadSymptoms()
      } catch (error) {
        this.$toasted.error(error.response?.data?.message || 'Failed to log symptoms')
      } finally {
        this.isLoading = false
      }
    },

    addSymptom(newSymptom) {
      this.availableSymptoms.push(newSymptom)
      this.symptomsForm.symptoms.push(newSymptom)
    },

    formatDate(date) {
      return this.$moment(date).format('MMM D, YYYY')
    },

    getDuration(start, end) {
      return this.$moment(end).diff(this.$moment(start), 'days') + 1
    },

    resetForm() {
      this.form = {
        start_date: null,
        end_date: null
      }
    },

    resetSymptomsForm() {
      this.symptomsForm = {
        date: new Date(),
        symptoms: []
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.symptoms-list {
  max-height: 300px;
  overflow-y: auto;
}

.symptoms-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}
</style>