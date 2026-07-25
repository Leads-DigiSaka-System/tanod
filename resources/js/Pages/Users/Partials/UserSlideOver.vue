<template>
  <SlideOver :show="show" max-width="2xl" :title="title" :subtitle="subtitle" @close="emit('close')">
    <div v-if="action === 'view' && user" class="flex-1 overflow-y-auto">
      <div class="p-6">
        <div class="flex flex-col items-center border-b border-gray-200 pb-6 text-center dark:border-gray-700">
          <img v-if="user.profile_photo_path" :src="`/storage/${user.profile_photo_path}`" :alt="user.name" class="h-24 w-24 rounded-full object-cover ring-4 ring-gray-100 dark:ring-gray-700" />
          <div v-else class="flex h-24 w-24 items-center justify-center rounded-full bg-emerald-100 ring-4 ring-gray-100 dark:bg-emerald-900 dark:ring-gray-700">
            <span class="text-3xl font-bold text-emerald-700 dark:text-emerald-300">{{ user.name?.charAt(0)?.toUpperCase() }}</span>
          </div>
          <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">{{ user.name }}</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatRoleName(user.roles?.[0]) || 'No role' }}</p>
          <StatusBadge :status="user.is_active ? 'online' : 'offline'" :label="user.is_active ? 'Active' : 'Inactive'" class="mt-2" />
        </div>

        <dl class="grid grid-cols-1 gap-5 py-6 sm:grid-cols-2">
          <div>
            <dt class="text-xs font-semibold uppercase text-gray-400">Email</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ user.email }}</dd>
          </div>
          <div>
            <dt class="text-xs font-semibold uppercase text-gray-400">Phone</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ user.phone || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-semibold uppercase text-gray-400">Gender</dt>
            <dd class="mt-1 text-sm capitalize text-gray-900 dark:text-white">{{ user.gender || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-semibold uppercase text-gray-400">Joined</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(user.created_at) }}</dd>
          </div>
          <div v-if="user.roles?.[0]?.name === 'tps'" class="sm:col-span-2">
            <dt class="text-xs font-semibold uppercase text-gray-400">Tractor Access</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ user.tps_assign_all_tractors ? 'Full fleet access' : 'Group responsibilities only' }}</dd>
          </div>
        </dl>
      </div>

      <div class="sticky bottom-0 flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
        <button type="button" class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600" @click="emit('close')">Close</button>
        <button type="button" class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-800" @click="emit('edit', user)">Edit User</button>
      </div>
    </div>

    <form v-else @submit.prevent="submit" class="flex-1 overflow-y-auto">
      <div class="space-y-5 p-6">
        <div>
          <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Full Name <span class="text-red-500">*</span></label>
          <input v-model="form.name" type="text" placeholder="John Doe" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
          <p v-if="form.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Email <span class="text-red-500">*</span></label>
            <input v-model="form.email" type="email" placeholder="john@example.com" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.email }}</p>
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Phone <span class="text-red-500">*</span></label>
            <input v-model="form.phone" type="text" placeholder="09xxxxxxxxx" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
            <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.phone }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ action === 'edit' ? 'New Password' : 'Password' }}
              <span v-if="action === 'create'" class="text-red-500">*</span>
              <span v-else class="font-normal text-gray-400">(leave blank to keep)</span>
            </label>
            <input v-model="form.password" type="password" placeholder="Min 8 characters" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.password }}</p>
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm Password <span v-if="action === 'create'" class="text-red-500">*</span></label>
            <input v-model="form.password_confirmation" type="password" placeholder="Re-enter password" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div v-if="action === 'create' && createMode === 'farmer'">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">FCA</label>
            <select v-model="form.fca_id" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
              <option value="">Select FCA</option>
              <option v-for="fca in fcaList" :key="fca.id" :value="fca.id">{{ fca.name }}</option>
            </select>
            <p v-if="form.errors.fca_id" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.fca_id }}</p>
          </div>
          <div v-else>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Role <span class="text-red-500">*</span></label>
            <select v-model="form.role" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
              <option value="">Select Role</option>
              <option v-for="role in availableRoles" :key="role.id" :value="role.name">{{ formatRoleName(role) }}</option>
            </select>
            <p v-if="form.errors.role" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.role }}</p>
          </div>
          <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Gender</label>
            <select v-model="form.gender" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
              <option value="">Select</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
            <p v-if="form.errors.gender" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.gender }}</p>
          </div>
        </div>

        <div v-if="form.role === 'tps'" class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
          <label class="flex items-start gap-3">
            <input v-model="form.tps_assign_all_tractors" type="checkbox" class="mt-1 h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700" />
            <span>
              <span class="block text-sm font-medium text-gray-900 dark:text-white">Assign this TPS to all tractors</span>
              <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">Leave this off to manage tractor visibility through group responsibilities.</span>
            </span>
          </label>
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Profile Photo</label>
          <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
              <img v-if="photoPreview" :src="photoPreview" alt="Profile preview" class="h-12 w-12 rounded-full object-cover" />
              <span v-else class="text-lg font-semibold text-gray-400">{{ form.name?.charAt(0)?.toUpperCase() || '?' }}</span>
            </div>
            <label class="cursor-pointer rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
              Choose photo
              <input type="file" accept="image/*" class="sr-only" @change="handlePhoto" />
            </label>
          </div>
          <p v-if="form.errors.profile_photo" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.profile_photo }}</p>
        </div>
      </div>

      <div class="sticky bottom-0 flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
        <button type="button" class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600" @click="emit('close')">Cancel</button>
        <button type="submit" :disabled="form.processing" class="inline-flex items-center rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-50">
          {{ form.processing ? 'Saving...' : submitLabel }}
        </button>
      </div>
    </form>
  </SlideOver>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import SlideOver from '@/Components/SlideOver.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatDate } from '@/utils/dateFormat';
import { formatRoleName } from '@/utils/roleFormat';

const props = defineProps({
  show: { type: Boolean, default: false },
  action: { type: String, default: 'create' },
  createMode: { type: String, default: 'regular' },
  user: { type: Object, default: null },
  roles: { type: Array, default: () => [] },
  regularRoles: { type: Array, default: () => [] },
  fcaList: { type: Array, default: () => [] },
  defaultFcaId: { type: [Number, String], default: '' },
});

const emit = defineEmits(['close', 'edit']);
const photoPreview = ref(null);

const form = useForm({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  gender: '',
  role: '',
  tps_assign_all_tractors: false,
  profile_photo: null,
  fca_id: '',
});

const title = computed(() => {
  if (props.action === 'view') return props.user?.name || 'User Details';
  if (props.action === 'edit') return `Edit ${props.user?.name || 'User'}`;
  if (props.createMode === 'farmer') return 'Add Farmer Member';
  if (props.createMode === 'fca') return 'Add New FCA';
  return 'Add New User';
});

const subtitle = computed(() => {
  if (props.action === 'view') return 'Account information and access details.';
  if (props.action === 'edit') return 'Update account information and access.';
  if (props.createMode === 'farmer') return 'Create a farmer account under an FCA.';
  if (props.createMode === 'fca') return 'Create a new FCA account.';
  return 'Fill in the details to create a user account.';
});

const submitLabel = computed(() => {
  if (props.action === 'edit') return 'Update User';
  if (props.createMode === 'farmer') return 'Create Farmer';
  if (props.createMode === 'fca') return 'Create FCA';
  return 'Create User';
});

const availableRoles = computed(() => {
  if (props.action === 'edit') return props.roles;
  if (props.createMode === 'fca') return props.roles.filter(role => role.name === 'fca');
  return props.regularRoles;
});

const resetForm = () => {
  form.clearErrors();
  form.reset();
  form.name = props.user?.name || '';
  form.email = props.user?.email || '';
  form.phone = props.user?.phone || '';
  form.gender = props.user?.gender || '';
  form.role = props.action === 'edit'
    ? props.user?.roles?.[0]?.name || ''
    : props.createMode === 'fca'
      ? 'fca'
      : props.createMode === 'farmer'
        ? 'farmer'
        : '';
  form.tps_assign_all_tractors = Boolean(props.user?.tps_assign_all_tractors);
  form.fca_id = props.defaultFcaId || '';
  photoPreview.value = props.user?.profile_photo_path ? `/storage/${props.user.profile_photo_path}` : null;
};

watch(() => [props.show, props.action, props.createMode, props.user?.id, props.defaultFcaId], () => {
  if (props.show) resetForm();
});

const handlePhoto = (event) => {
  const file = event.target.files?.[0];
  if (!file) return;

  form.profile_photo = file;
  photoPreview.value = URL.createObjectURL(file);
};

const submit = () => {
  const isEdit = props.action === 'edit' && props.user;
  const url = isEdit ? `/users/${props.user.id}` : '/users';

  form.transform(data => isEdit ? { ...data, _method: 'PUT' } : data)
    .post(url, {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => emit('close'),
    });
};
</script>
