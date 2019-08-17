# submissions.atlasacademy.io

submissions.atlasacademy.io is a API service intended for
connecting the submissions and drop rate sheets with other
outside services and devices.

### Available Routes

- GET /event
- GET /event/{uid}
- GET /event/{event_uid}/{event_node_uid}/submissions
- POST /submit/run
- POST /submit/revert

### /event

Returns
```javascript
[event, ...]
```

### /event/{uid}
Returns
```javascript
{
  ...event,
  nodes: [node, ...],
  drops: [drop, ...],
  node_drops: [node_drop, ...]
}
```

### /event/{event_uid}/{event_node_uid}/submissions
Expects
```javascript
{
    after_receipt: receipt
}
```

Returns
```javascript
[submission, ...]
```

### /submit/run
Expects
```javascript
{
  event_uid: event.uid,
  event_node_uid: event.nodes[#].uid,
  submitter: "Optional",
  token: "Needs to be set if wanted to revert submission",
  drops: [
    {
        uid: event.node_drops[#].uid,
        quantity: event.node_drops[#].quantity,
        count: Number,
        ignored: Boolean
    }
    ...
  ]
}
```

Returns
```javascript
{
  status: "Success",
  receipt: "Receipt id",
  missing_drops: "Bool. Indicates if submissions was missing drops (not ignored). Helps app know if refresh of data is required."
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

### Submission Object

```javascript
{
  receipt: "Unique receipt id",
  event_uid: "Event uid",
  event_node_uid: "Event Node uid",
  submitter: "Name of submitter",
  drops: [
    {
      uid: "Drop uid",
      quantity: "Drop quantity. The multiplier for the drop.",
      count: "Drop count. The value that is submitted by the user.",
      ignored: "Bool on whether user didn't track drop"
    },
    ...
  ]
}
```
