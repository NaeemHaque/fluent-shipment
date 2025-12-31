<template>
  <div class="recent-activities-wrap">
    <div v-if="activities.length" class="activities-body">
      <div v-for="(activity, index) in activities" :key="index" class="activity-item">
        <div class="activity-icon">
          <div class="icon-wrapper" :style="{ backgroundColor: getActivityColor(activity.type) }">
            <div v-html="getActivityIcon(activity.type)"></div>
          </div>
        </div>
        
        <div class="activity-content">
          <div class="activity-title">{{ activity.title }}</div>
          <div class="activity-description">{{ activity.description }}</div>
          <div class="activity-meta">
            <span class="activity-time">{{ formatTime(activity.created_at) }}</span>
            <span class="activity-author" v-if="activity.created_by">by {{ activity.created_by }}</span>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="activities-empty">
      <div class="empty-icon">
        <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M24 4L6 14L24 24L42 14L24 4Z" stroke="var(--fluentshipment-secondary-text)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M6 24L24 34L42 24" stroke="var(--fluentshipment-secondary-text)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M6 34L24 44L42 34" stroke="var(--fluentshipment-secondary-text)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <div class="empty-text">No recent activities</div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'RecentActivity',
  props: {
    activities: {
      type: Array,
      default: () => []
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  methods: {
    formatTime(dateTime) {
      if (!dateTime) return '';
      const date = new Date(dateTime);
      const now = new Date();
      const diffTime = Math.abs(now - date);
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
      
      if (diffDays === 1) {
        return 'Today';
      } else if (diffDays === 2) {
        return 'Yesterday';
      } else if (diffDays <= 7) {
        return `${diffDays - 1} days ago`;
      } else {
        return date.toLocaleDateString();
      }
    },
    
    getActivityColor(type) {
      const colors = {
        'shipment_created': '#E0FAEC',
        'shipment_updated': '#EBF1FF',
        'shipment_delivered': '#EFFDF7',
        'shipment_cancelled': '#FFEBF4',
        'order_shipped': '#FFF3EB',
        'tracking_updated': '#EFEBFF'
      };
      return colors[type] || '#F3F4F6';
    },
    
    getActivityIcon(type) {
      const icons = {
        'shipment_created': `<svg width="16" height="16" viewBox="0 0 16 16" fill="#1FC16B">
          <path d="M8 1L15 4.5V11.5L8 15L1 11.5V4.5L8 1Z" stroke="#1FC16B" stroke-width="2" fill="none"/>
          <path d="M8 8L15 4.5" stroke="#1FC16B" stroke-width="2"/>
          <path d="M8 8L1 4.5" stroke="#1FC16B" stroke-width="2"/>
          <path d="M8 8V15" stroke="#1FC16B" stroke-width="2"/>
        </svg>`,
        'shipment_updated': `<svg width="16" height="16" viewBox="0 0 16 16" fill="#335CFF">
          <path d="M8 2V8L12 10" stroke="#335CFF" stroke-width="2" stroke-linecap="round"/>
          <circle cx="8" cy="8" r="6" stroke="#335CFF" stroke-width="2" fill="none"/>
        </svg>`,
        'shipment_delivered': `<svg width="16" height="16" viewBox="0 0 16 16" fill="#10B981">
          <path d="M14 4L6 12L2 8" stroke="#10B981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>`,
        'shipment_cancelled': `<svg width="16" height="16" viewBox="0 0 16 16" fill="#EF4444">
          <path d="M12 4L4 12" stroke="#EF4444" stroke-width="2" stroke-linecap="round"/>
          <path d="M4 4L12 12" stroke="#EF4444" stroke-width="2" stroke-linecap="round"/>
        </svg>`,
        'order_shipped': `<svg width="16" height="16" viewBox="0 0 16 16" fill="#F59E0B">
          <path d="M1 12V3H13L15 8V12H1Z" stroke="#F59E0B" stroke-width="2" fill="none"/>
          <circle cx="4" cy="12" r="2" stroke="#F59E0B" stroke-width="2" fill="none"/>
          <circle cx="12" cy="12" r="2" stroke="#F59E0B" stroke-width="2" fill="none"/>
        </svg>`,
        'tracking_updated': `<svg width="16" height="16" viewBox="0 0 16 16" fill="#8B5CF6">
          <path d="M8 2L3 7L8 12L13 7L8 2Z" stroke="#8B5CF6" stroke-width="2" fill="none"/>
          <path d="M8 6V10" stroke="#8B5CF6" stroke-width="2"/>
          <path d="M6 8H10" stroke="#8B5CF6" stroke-width="2"/>
        </svg>`
      };
      return icons[type] || icons['shipment_created'];
    }
  }
}
</script>

<style scoped>
.recent-activities-wrap {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.activities-header {
  padding: 0 20px 16px 20px;
  border-bottom: 1px solid var(--fluentshipment-primary-border);
}

.header-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.activities-count {
  color: var(--fluentshipment-title-text-color);
  font-family: Inter;
  font-size: 24px;
  font-style: normal;
  font-weight: 600;
  line-height: 32px;
}

.activities-label {
  color: var(--fluentshipment-secondary-text);
  font-family: Inter;
  font-size: 14px;
  font-style: normal;
  font-weight: 400;
  line-height: 20px;
}

.activities-body {
  flex: 1;
  max-height: 450px;
  overflow-y: auto;
}

.activity-item {
  display: flex;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid var(--fluentshipment-primary-border);
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-icon {
  flex-shrink: 0;
}

.icon-wrapper {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.activity-content {
  flex: 1;
  min-width: 0;
}

.activity-title {
  color: var(--fluentshipment-title-text-color);
  font-family: Inter;
  font-size: 14px;
  font-style: normal;
  font-weight: 500;
  line-height: 20px;
  margin-bottom: 4px;
}

.activity-description {
  color: var(--fluentshipment-secondary-text);
  font-family: Inter;
  font-size: 13px;
  font-style: normal;
  font-weight: 400;
  line-height: 18px;
  margin-bottom: 6px;
}

.activity-meta {
  display: flex;
  gap: 8px;
  align-items: center;
}

.activity-time {
  color: var(--fluentshipment-secondary-text);
  font-family: Inter;
  font-size: 12px;
  font-style: normal;
  font-weight: 400;
  line-height: 16px;
}

.activity-author {
  color: var(--fluentshipment-secondary-text);
  font-family: Inter;
  font-size: 12px;
  font-style: normal;
  font-weight: 400;
  line-height: 16px;
}

.activities-empty {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
}

.empty-icon {
  margin-bottom: 12px;
  opacity: 0.5;
}

.empty-text {
  color: var(--fluentshipment-secondary-text);
  font-family: Inter;
  font-size: 14px;
  font-style: normal;
  font-weight: 400;
  line-height: 20px;
}
</style>
