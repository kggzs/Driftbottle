package com.driftbottle.app.ui.screens.bottle

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.driftbottle.app.R
import com.driftbottle.app.data.model.response.BottleResponse
import com.driftbottle.app.ui.viewmodel.BottleViewModel

@Composable
fun UserBottlesScreen(
    type: BottleType,
    onBottleClick: (Int) -> Unit,
    onBack: () -> Unit
) {
    val viewModel = androidx.lifecycle.viewmodel.compose.viewModel<BottleViewModel>()
    val bottles by remember {
        mutableStateOf<List<BottleResponse>>(emptyList())
    }
    val isLoading by remember {
        mutableStateOf(false)
    }
    val error by remember {
        mutableStateOf<String?>(null)
    }

    LaunchedEffect(type) {
        // 这里应该根据类型加载不同的瓶子列表
        // if (type == BottleType.MY_BOTTLES) {
        //     viewModel.loadMyBottles()
        // } else {
        //     viewModel.loadPickedBottles()
        // }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        text = if (type == BottleType.MY_BOTTLES) {
                            stringResource(R.string.my_bottles)
                        } else {
                            stringResource(R.string.picked_bottles)
                        }
                    )
                },
                navigationIcon = {
                    IconButton(onClick = onBack) {
                        Icon(
                            androidx.compose.material.icons.Icons.Default.ArrowBack,
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
        ) {
            if (isLoading) {
                Box(
                    modifier = Modifier.fillMaxSize(),
                    contentAlignment = Alignment.Center
                ) {
                    CircularProgressIndicator()
                }
            } else if (error != null) {
                Box(
                    modifier = Modifier.fillMaxSize(),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = error ?: stringResource(R.string.network_error),
                        color = MaterialTheme.colorScheme.error
                    )
                }
            } else if (bottles.isEmpty()) {
                Box(
                    modifier = Modifier.fillMaxSize(),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = if (type == BottleType.MY_BOTTLES) {
                            "你还没有扔过漂流瓶"
                        } else {
                            "你还没有捡到过漂流瓶"
                        },
                        style = MaterialTheme.typography.bodyLarge
                    )
                }
            } else {
                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    contentPadding = PaddingValues(16.dp)
                ) {
                    items(bottles) {
                        BottleItem(
                            bottle = it,
                            onClick = { onBottleClick(it.id ?: 0) }
                        )
                        Spacer(modifier = Modifier.height(16.dp))
                    }
                }
            }
        }
    }
}

enum class BottleType {
    MY_BOTTLES,
    PICKED_BOTTLES
}

@Composable
fun BottleItem(
    bottle: BottleResponse,
    onClick: () -> Unit
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        onClick = onClick
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                mainAxisAlignment = MainAxisAlignment.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = bottle.username ?: stringResource(R.string.anonymous),
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold
                )
                Text(
                    text = bottle.created_at ?: "",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
            Spacer(modifier = Modifier.height(12.dp))
            Text(
                text = bottle.content ?: "",
                style = MaterialTheme.typography.bodyMedium,
                maxLines = 3,
                overflow = androidx.compose.ui.text.style.TextOverflow.Ellipsis
            )
            if (!bottle.audio_url.isNullOrEmpty()) {
                Spacer(modifier = Modifier.height(8.dp))
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(40.dp)
                        .background(MaterialTheme.colorScheme.surfaceVariant)
                        .padding(8.dp),
                    contentAlignment = Alignment.CenterStart
                ) {
                    Row(
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Icon(
                            androidx.compose.material.icons.Icons.Default.Mic,
                            contentDescription = stringResource(R.string.audio_bottle),
                            modifier = Modifier.size(20.dp)
                        )
                        Spacer(modifier = Modifier.width(8.dp))
                        Text(stringResource(R.string.audio_bottle))
                    }
                }
            }
            Spacer(modifier = Modifier.height(12.dp))
            Row(
                modifier = Modifier.fillMaxWidth(),
                mainAxisAlignment = MainAxisAlignment.SpaceBetween
            ) {
                Row(
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Icon(
                        if (bottle.is_liked == true) {
                            androidx.compose.material.icons.Icons.Default.Favorite
                        } else {
                            androidx.compose.material.icons.Icons.Default.FavoriteBorder
                        },
                        contentDescription = stringResource(R.string.likes),
                        modifier = Modifier.size(16.dp)
                    )
                    Spacer(modifier = Modifier.width(4.dp))
                    Text(
                        text = (bottle.likes_count ?: 0).toString(),
                        style = MaterialTheme.typography.bodySmall
                    )
                }
                Row(
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Icon(
                        androidx.compose.material.icons.Icons.Default.Comment,
                        contentDescription = stringResource(R.string.comments),
                        modifier = Modifier.size(16.dp)
                    )
                    Spacer(modifier = Modifier.width(4.dp))
                    Text(
                        text = (bottle.comments_count ?: 0).toString(),
                        style = MaterialTheme.typography.bodySmall
                    )
                }
            }
        }
    }
}
