# Poetrax API Documentation

## Базовый URL
`https://poetrax.online/api`

## Аутентификация
- Сессии (для веба)
- JWT токен (для API клиентов)

## Эндпоинты

### Треки
| Метод | URL | Описание |
|-------|-----|----------|
| GET | `/tracks` | Список треков (с пагинацией) |
| GET | `/tracks/{id}` | Детали трека |
| GET | `/tracks/popular` | Популярные треки |
| GET | `/tracks/recent` | Новые треки |
| GET | `/tracks/search?q={query}` | Поиск треков |
| POST | `/tracks/{id}/play` | Записать прослушивание |
| POST | `/tracks/{id}/like` | Поставить лайк |
| DELETE | `/tracks/{id}/like` | Убрать лайк |
| POST | `/tracks/{id}/bookmark` | Добавить в закладки |
| DELETE | `/tracks/{id}/bookmark` | Убрать из закладок |
| POST | `/tracks/{id}/share` | Поделиться |

### Поэты
| Метод | URL | Описание |
|-------|-----|----------|
| GET | `/poets` | Список поэтов |
| GET | `/poets/{id}` | Детали поэта |
| GET | `/poets/{id}/tracks` | Треки поэта |
| GET | `/poets/{id}/poems` | Стихи поэта |
| GET | `/poets/popular` | Популярные поэты |
| GET | `/poets/random` | Случайные поэты |

### Стихи
| Метод | URL | Описание |
|-------|-----|----------|
| GET | `/poems` | Список стихов |
| GET | `/poems/{id}` | Детали стиха |
| GET | `/poems/popular` | Популярные стихи |
| GET | `/poems/random` | Случайное стихотворение |

### Рекомендации
| Метод | URL | Описание |
|-------|-----|----------|
| GET | `/recommendations/user` | Персонализированные |
| GET | `/recommendations/popular` | Популярное |
| GET | `/recommendations/new` | Новинки |
| GET | `/recommendations/trending` | Тренды |
| GET | `/recommendations/track/{id}` | Похожие на трек |
| GET | `/recommendations/poet/{id}` | Для поэта |
| GET | `/recommendations/poem/{id}` | Для стиха |

### Фильтрация
| Метод | URL | Описание |
|-------|-----|----------|
| GET | `/filters/available/{entity}` | Доступные фильтры |
| POST | `/filter/tracks` | Фильтр треков |
| POST | `/filter/poets` | Фильтр поэтов |
| POST | `/filter/poems` | Фильтр стихов |
| POST | `/filter/users` | Фильтр пользователей |

### Пользователи
| Метод | URL | Описание |
|-------|-----|----------|
| GET | `/user/profile` | Профиль |
| GET | `/user/tracks` | Мои треки |
| GET | `/user/liked` | Понравившиеся |
| GET | `/user/bookmarks` | Закладки |
| POST | `/user/settings` | Сохранить настройки |

## Примеры запросов

### Получить треки с фильтром
```bash
curl -X POST https://poetrax.online/api/filter/tracks \
  -H "Content-Type: application/json" \
  -d '{"genre_id":5, "mood_id":3, "limit":20}'
Поставить лайк
bash
curl -X POST https://poetrax.online/api/tracks/123/like \
  -H "Authorization: Bearer {token}"
Поиск
bash
curl "https://poetrax.online/api/tracks/search?q=есенин"
Коды ответов
•	200 - Успех
•	201 - Создано
•	400 - Неверный запрос
•	401 - Не авторизован
•	403 - Доступ запрещён
•	404 - Не найдено
•	500 - Ошибка сервера
