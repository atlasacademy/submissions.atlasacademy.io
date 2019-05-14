# submissions.atlasacademy.io

submissions.atlasacademy.io is a API service intended for
connecting the submissions and drop rate sheets with other
outside services and devices.

### Available Routes

- GET /event
- GET /event/{uid}
- POST /submit/run

### /event

Returns
```javascript
[...events]
```

### /event/{uid}
Returns
```javascript
{
  ...event,
  nodes: [...nodes],
  drops: [...drops],
  node_drops: [...node_drops]
}
```

### /submit/run
Expects
```javascript
{
  event_uid: event.uid,
  event_node_uid: event.nodes[#].uid,
  submitter: "",
  drops: [
    uid: event.node_drops[#].uid,
    quantity: event.node_drops[#].quantity,
    count: Number,
    ignored: Boolean
  ]
}
```

### Event Object

```javascript
{
  uid: "Unique string identifier",
  sheet_type: "Internal flag for import/export adapter",
  sheet_id: "Google sheets id",
  name: "Event name",
  node_filter: "Regex to filter out only applicable nodes",
  submittable: "Bool of if event allows /submit requests"
}
```

### Node Object

```javascript
{
  event_uid: "Event uid",
  uid: "Unique string identifier",
  name: "Node name",
  sheet_name: "Google sheet tab",
  submissions: "Number of total submissions",
  submitters: "Number of unique submitters"
}
```

### Drops Object

```javascript
{
  uid: "Drop uid",
  name: "Drop name",
  type: "Bonus Rate-Up, Material, or QP",
  quantity: "Some drops have their own embedded quantity. Such as Q050 = 5,000 QP",
  image: "Url to hosted image",
  image_original: "Url to original image",
  event: "Bool of if this drop is event only"
}
```

### NodeDrops Object

```javascript
{
  event_uid: "Event uid",
  event_node_uid: "Event Node uid",
  uid: "Drop uid",
  quantity: "This quantity field overrides the drops.quantity field if enabled",
  rate: "Drop rate",
  apd: "AP per drop",
  count: "Number of drops recorded",
  submissions: "Number of submissions recorded"
}
```
