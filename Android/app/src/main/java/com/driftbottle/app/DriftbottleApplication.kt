package com.driftbottle.app

import android.app.Application
import dagger.hilt.android.HiltAndroidApp

@HiltAndroidApp
class DriftbottleApplication : Application() {
    override fun onCreate() {
        super.onCreate()
        // 初始化应用
    }
}
