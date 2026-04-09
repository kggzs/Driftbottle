package com.driftbottle.app.ui.screens.main

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import com.driftbottle.app.DriftbottleApplication
import com.driftbottle.app.data.repository.AuthRepository
import com.driftbottle.app.ui.screens.auth.LoginScreen
import com.driftbottle.app.ui.screens.auth.RegisterScreen
import com.driftbottle.app.ui.viewmodel.LoginViewModel
import com.driftbottle.app.ui.viewmodel.RegisterViewModel
import com.driftbottle.app.ui.theme.DriftbottleTheme
import dagger.hilt.android.AndroidEntryPoint
import javax.inject.Inject

@AndroidEntryPoint
class MainActivity : ComponentActivity() {
    
    @Inject
    lateinit var authRepository: AuthRepository
    
    @Inject
    lateinit var loginViewModel: LoginViewModel
    
    @Inject
    lateinit var registerViewModel: RegisterViewModel
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent {
            DriftbottleTheme {
                var isLoggedIn by remember {
                    mutableStateOf(false)
                }
                var showRegister by remember {
                    mutableStateOf(false)
                }
                
                if (isLoggedIn) {
                    MainScreen()
                } else if (showRegister) {
                    RegisterScreen(
                        viewModel = registerViewModel,
                        onRegisterSuccess = {
                            showRegister = false
                        },
                        onNavigateToLogin = {
                            showRegister = false
                        }
                    )
                } else {
                    LoginScreen(
                        viewModel = loginViewModel,
                        onLoginSuccess = {
                            isLoggedIn = true
                        },
                        onNavigateToRegister = {
                            showRegister = true
                        }
                    )
                }
            }
        }
    }
}
