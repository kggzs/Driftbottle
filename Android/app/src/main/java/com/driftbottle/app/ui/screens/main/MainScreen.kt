package com.driftbottle.app.ui.screens.main

import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.unit.dp
import com.driftbottle.app.R
import com.driftbottle.app.ui.screens.bottle.BottleDetailScreen
import com.driftbottle.app.ui.screens.bottle.UserBottlesScreen
import com.driftbottle.app.ui.screens.bottle.BottleType

@Composable
fun MainScreen() {
    val context = LocalContext.current
    var currentScreen by remember { mutableStateOf(0) }
    var showBottleDetail by remember { mutableStateOf(false) }
    var selectedBottleId by remember { mutableStateOf(0) }
    var showUserBottles by remember { mutableStateOf(false) }
    var userBottleType by remember { mutableStateOf(BottleType.MY_BOTTLES) }
    
    Scaffold(
        bottomBar = {
            NavigationBar {
                NavigationBarItem(
                    icon = { Icon(androidx.compose.material.icons.Icons.Default.Home, contentDescription = stringResource(R.string.home)) },
                    label = { Text(stringResource(R.string.home)) },
                    selected = currentScreen == 0,
                    onClick = { currentScreen = 0 }
                )
                NavigationBarItem(
                    icon = { Icon(androidx.compose.material.icons.Icons.Default.Send, contentDescription = stringResource(R.string.throw_bottle)) },
                    label = { Text(stringResource(R.string.throw_bottle)) },
                    selected = currentScreen == 1,
                    onClick = { currentScreen = 1 }
                )
                NavigationBarItem(
                    icon = { Icon(androidx.compose.material.icons.Icons.Default.Favorite, contentDescription = stringResource(R.string.pick_bottle)) },
                    label = { Text(stringResource(R.string.pick_bottle)) },
                    selected = currentScreen == 2,
                    onClick = { currentScreen = 2 }
                )
                NavigationBarItem(
                    icon = { Icon(androidx.compose.material.icons.Icons.Default.Person, contentDescription = stringResource(R.string.profile)) },
                    label = { Text(stringResource(R.string.profile)) },
                    selected = currentScreen == 3,
                    onClick = { currentScreen = 3 }
                )
            }
        }
    ) {
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(it)
        ) {
            when (currentScreen) {
                0 -> HomeScreen(
                    onBottleClick = { bottleId ->
                        selectedBottleId = bottleId
                        showBottleDetail = true
                    }
                )
                1 -> ThrowBottleScreen()
                2 -> PickBottleScreen(
                    onBottleClick = { bottleId ->
                        selectedBottleId = bottleId
                        showBottleDetail = true
                    }
                )
                3 -> ProfileScreen(
                    onMyBottlesClick = {
                        userBottleType = BottleType.MY_BOTTLES
                        showUserBottles = true
                    },
                    onPickedBottlesClick = {
                        userBottleType = BottleType.PICKED_BOTTLES
                        showUserBottles = true
                    }
                )
            }
            
            if (showBottleDetail) {
                BottleDetailScreen(
                    bottleId = selectedBottleId,
                    onBack = { showBottleDetail = false }
                )
            }
            
            if (showUserBottles) {
                UserBottlesScreen(
                    type = userBottleType,
                    onBottleClick = { bottleId ->
                        selectedBottleId = bottleId
                        showUserBottles = false
                        showBottleDetail = true
                    },
                    onBack = { showUserBottles = false }
                )
            }
        }
    }
}

@Composable
fun HomeScreen(
    onBottleClick: (Int) -> Unit
) {
    Box(
        modifier = Modifier.fillMaxSize(),
        contentAlignment = Alignment.Center
    ) {
        Column(
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center
        ) {
            Text(
                text = stringResource(R.string.home),
                style = MaterialTheme.typography.headlineLarge
            )
            Spacer(modifier = Modifier.height(16.dp))
            Text(
                text = "欢迎使用漂流瓶",
                style = MaterialTheme.typography.bodyLarge
            )
            Spacer(modifier = Modifier.height(24.dp))
            Button(onClick = { onBottleClick(1) }) {
                Text("查看示例漂流瓶")
            }
        }
    }
}

@Composable
fun ThrowBottleScreen() {
    Box(
        modifier = Modifier.fillMaxSize(),
        contentAlignment = Alignment.Center
    ) {
        Column(
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center
        ) {
            Text(
                text = stringResource(R.string.throw_bottle),
                style = MaterialTheme.typography.headlineLarge
            )
            Spacer(modifier = Modifier.height(16.dp))
            Text(
                text = "写下你的心情，扔进大海",
                style = MaterialTheme.typography.bodyLarge
            )
        }
    }
}

@Composable
fun PickBottleScreen(
    onBottleClick: (Int) -> Unit
) {
    Box(
        modifier = Modifier.fillMaxSize(),
        contentAlignment = Alignment.Center
    ) {
        Column(
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center
        ) {
            Text(
                text = stringResource(R.string.pick_bottle),
                style = MaterialTheme.typography.headlineLarge
            )
            Spacer(modifier = Modifier.height(16.dp))
            Text(
                text = "从大海中捡起一个漂流瓶",
                style = MaterialTheme.typography.bodyLarge
            )
            Spacer(modifier = Modifier.height(24.dp))
            Button(onClick = { onBottleClick(2) }) {
                Text("捡一个漂流瓶")
            }
        }
    }
}

@Composable
fun ProfileScreen(
    onMyBottlesClick: () -> Unit,
    onPickedBottlesClick: () -> Unit
) {
    Box(
        modifier = Modifier.fillMaxSize(),
        contentAlignment = Alignment.Center
    ) {
        Column(
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center
        ) {
            Text(
                text = stringResource(R.string.profile),
                style = MaterialTheme.typography.headlineLarge
            )
            Spacer(modifier = Modifier.height(24.dp))
            Button(onClick = onMyBottlesClick) {
                Text(stringResource(R.string.my_bottles))
            }
            Spacer(modifier = Modifier.height(16.dp))
            Button(onClick = onPickedBottlesClick) {
                Text(stringResource(R.string.picked_bottles))
            }
        }
    }
}
