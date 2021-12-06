import { createRouter, createWebHashHistory } from 'vue-router'
import Home from '@/views/Home.vue'
import About from '@/views/About.vue'
import Method from '@/views/Method.vue'
import Application from '@/views/Application.vue'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: Home
  },
  {
    path: '/about',
    name: 'About',
    component: About
  },
  {
    path: '/app/:name',
    name: 'Application',
    component: Application
  },
  {
    path: '/method/:name',
    name: 'Method',
    component: Method
  }
]

const router = createRouter({
  history: createWebHashHistory(),
  routes
})

export default router
