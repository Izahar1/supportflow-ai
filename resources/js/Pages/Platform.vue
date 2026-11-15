<script setup lang="ts">
import {Head} from '@inertiajs/vue3';import Workspace from '@/Layouts/Workspace.vue';defineProps<{product:any;workspaces:any}>();
</script><template><Head title="Platform"/><Workspace :product="product"><h1>Platform directory</h1><p class="muted">Workspace lifecycle metadata.</p><section class="panel"><div class="activity" v-for="w in workspaces.data" :key="w.id"><strong>{{w.name}}</strong><span>{{w.slug}}</span><span class="badge">{{w.status}}</span><span>{{w.provisioning_status}}</span></div></section></Workspace></template>
