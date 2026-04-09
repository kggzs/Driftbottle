package com.driftbottle.app.ui.screens.auth

import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.unit.dp
import com.driftbottle.app.R
import com.driftbottle.app.ui.viewmodel.RegisterViewModel

@Composable
fun RegisterScreen(
    viewModel: RegisterViewModel,
    onRegisterSuccess: () -> Unit,
    onNavigateToLogin: () -> Unit
) {
    val context = LocalContext.current
    val uiState by viewModel.uiState.collectAsState()
    
    var username by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var confirmPassword by remember { mutableStateOf("") }
    var gender by remember { mutableStateOf("男") }
    var passwordVisible by remember { mutableStateOf(false) }
    var confirmPasswordVisible by remember { mutableStateOf(false) }
    
    LaunchedEffect(uiState) {
        when (uiState) {
            is RegisterViewModel.RegisterUiState.Success -> {
                onRegisterSuccess()
            }
            is RegisterViewModel.RegisterUiState.Error -> {
                // 显示错误提示
            }
            else -> {}
        }
    }
    
    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(stringResource(R.string.register)) },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primary
                ),
                navigationIcon = {
                    IconButton(onClick = onNavigateToLogin) {
                        Icon(
                            imageVector = androidx.compose.material.icons.Icons.Default.ArrowBack,
                            contentDescription = stringResource(R.string.back)
                        )
                    }
                }
            )
        }
    ) {
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(it)
                .padding(24.dp),
            contentAlignment = Alignment.Center
        ) {
            Column(
                modifier = Modifier.fillMaxWidth(),
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                Text(
                    text = stringResource(R.string.app_name),
                    style = MaterialTheme.typography.headlineLarge,
                    color = MaterialTheme.colorScheme.primary
                )
                
                Spacer(modifier = Modifier.height(32.dp))
                
                OutlinedTextField(
                    value = username,
                    onValueChange = { username = it },
                    label = { Text(stringResource(R.string.username)) },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    enabled = uiState !is RegisterViewModel.RegisterUiState.Loading
                )
                
                Spacer(modifier = Modifier.height(16.dp))
                
                OutlinedTextField(
                    value = password,
                    onValueChange = { password = it },
                    label = { Text(stringResource(R.string.password)) },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    visualTransformation = if (passwordVisible) VisualTransformation.None else PasswordVisualTransformation(),
                    trailingIcon = {
                        IconButton(onClick = { passwordVisible = !passwordVisible }) {
                            val icon = if (passwordVisible) {
                                androidx.compose.material.icons.Icons.Default.Visibility
                            } else {
                                androidx.compose.material.icons.Icons.Default.VisibilityOff
                            }
                            Icon(imageVector = icon, contentDescription = null)
                        }
                    },
                    enabled = uiState !is RegisterViewModel.RegisterUiState.Loading
                )
                
                Spacer(modifier = Modifier.height(16.dp))
                
                OutlinedTextField(
                    value = confirmPassword,
                    onValueChange = { confirmPassword = it },
                    label = { Text("确认密码") },
                    modifier = Modifier.fillMaxWidth(),
                    singleLine = true,
                    visualTransformation = if (confirmPasswordVisible) VisualTransformation.None else PasswordVisualTransformation(),
                    trailingIcon = {
                        IconButton(onClick = { confirmPasswordVisible = !confirmPasswordVisible }) {
                            val icon = if (confirmPasswordVisible) {
                                androidx.compose.material.icons.Icons.Default.Visibility
                            } else {
                                androidx.compose.material.icons.Icons.Default.VisibilityOff
                            }
                            Icon(imageVector = icon, contentDescription = null)
                        }
                    },
                    enabled = uiState !is RegisterViewModel.RegisterUiState.Loading
                )
                
                Spacer(modifier = Modifier.height(16.dp))
                
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        text = stringResource(R.string.gender),
                        modifier = Modifier.weight(1f)
                    )
                    Row {
                        RadioButton(
                            selected = gender == "男",
                            onClick = { gender = "男" },
                            enabled = uiState !is RegisterViewModel.RegisterUiState.Loading
                        )
                        Text(text = "男")
                        Spacer(modifier = Modifier.width(24.dp))
                        RadioButton(
                            selected = gender == "女",
                            onClick = { gender = "女" },
                            enabled = uiState !is RegisterViewModel.RegisterUiState.Loading
                        )
                        Text(text = "女")
                    }
                }
                
                Spacer(modifier = Modifier.height(24.dp))
                
                Button(
                    onClick = {
                        if (password == confirmPassword) {
                            viewModel.register(username, password, gender)
                        }
                    },
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(56.dp),
                    enabled = username.isNotEmpty() && 
                            password.isNotEmpty() && 
                            confirmPassword.isNotEmpty() &&
                            password == confirmPassword &&
                            uiState !is RegisterViewModel.RegisterUiState.Loading
                ) {
                    if (uiState is RegisterViewModel.RegisterUiState.Loading) {
                        CircularProgressIndicator(
                            modifier = Modifier.size(24.dp),
                            color = MaterialTheme.colorScheme.onPrimary
                        )
                    } else {
                        Text(stringResource(R.string.register))
                    }
                }
                
                Spacer(modifier = Modifier.height(16.dp))
                
                TextButton(onClick = onNavigateToLogin) {
                    Text("已有账号？去登录")
                }
            }
        }
    }
}
