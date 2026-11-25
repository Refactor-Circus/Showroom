import { AtriumErrorCard as e, AtriumNoAccess as t, AtriumState as n, atriumStatus as r, defineAtriumModule as i, integrationData as a, mergeAtriumMessages as o, useAtriumI18n as s, useAtriumIntegration as c, useIntegrationQuery as l } from "@refactor-circus/atrium";
import { UBadge as u, UCard as d, ULink as f, UProgress as p, USkeleton as m } from "@refactor-circus/atrium/ui";
import { Fragment as h, computed as g, createBlock as _, createCommentVNode as v, createElementBlock as y, createElementVNode as b, createTextVNode as x, createVNode as S, defineComponent as C, onMounted as w, openBlock as T, renderList as E, shallowRef as D, toDisplayString as O, unref as k, withCtx as A } from "vue";
import { useI18n as j } from "vue-i18n";
// src/components/search/source.ts
function M(e, t, n) {
	let r = (e) => encodeURIComponent(e);
	switch (e) {
		case "products": return `/showroom/products/${r(t)}`;
		case "owners": return `/showroom/owners/${r(t)}`;
		case "attribute_groups": return `/showroom/attribute-groups/${r(n)}`;
		default: return `/showroom/${e}/${r(n)}`;
	}
}
var ee = async (e, { limit: t }) => {
	let { client: n } = c("showroom");
	if (!n) return [];
	let r = s(), { data: i } = a(await n.GET("/search", { params: { query: {
		q: e,
		limit: t
	} } }));
	return i.map((e) => ({
		title: e.label,
		subtitle: e.subtitle,
		icon: null,
		group: r.t(`showroom.search.groups.${e.type}`),
		link: {
			path: M(e.type, e.id, e.code),
			external: !1
		}
	}));
}, te = { "data-testid": "showroom-settings" }, N = {
	key: 3,
	class: "flex flex-col gap-4"
}, P = { class: "font-medium" }, ne = { class: "space-y-2 text-sm" }, F = ["data-row"], I = { class: "text-muted" }, L = { class: "font-mono tabular-nums" }, R = /* @__PURE__ */ C({
	__name: "SettingsPanel",
	props: { data: {} },
	setup(n) {
		let { t: i } = j(), { client: o } = c("showroom"), s = D(null), l = D(null);
		w(async () => {
			if (o) try {
				s.value = a(await o.GET("/settings-summary")).data;
			} catch (e) {
				l.value = e;
			}
		});
		let u = g(() => r(l.value) === 403), f = g(() => i("showroom.settings.off")), p = g(() => {
			let e = s.value;
			return e ? [{
				key: "api",
				title: i("showroom.settings.api"),
				rows: [
					{
						key: "authorization",
						label: i("showroom.settings.authorization"),
						value: e.authorization ? i("showroom.settings.on") : f.value
					},
					{
						key: "routes_prefix",
						label: i("showroom.settings.routes_prefix"),
						value: e.routes.enabled ? `/${e.routes.prefix}` : f.value
					},
					{
						key: "per_page",
						label: i("showroom.settings.per_page"),
						value: String(e.pagination.per_page)
					},
					{
						key: "max_per_page",
						label: i("showroom.settings.max_per_page"),
						value: String(e.pagination.max_per_page)
					}
				]
			}, {
				key: "mcp",
				title: i("showroom.settings.mcp"),
				rows: [{
					key: "web_transport",
					label: i("showroom.settings.web_transport"),
					value: e.mcp.web.enabled ? `/${e.mcp.web.route}` : f.value
				}, {
					key: "local_transport",
					label: i("showroom.settings.local_transport"),
					value: e.mcp.local.enabled ? e.mcp.local.handle : f.value
				}]
			}] : [];
		});
		return (n, r) => {
			let a = t, o = e, c = m, f = d;
			return T(), y("div", te, [k(u) ? (T(), _(a, { key: 0 })) : k(l) ? (T(), _(o, {
				key: 1,
				message: k(i)("atrium.settings.failed")
			}, null, 8, ["message"])) : k(s) ? (T(), y("div", N, [(T(!0), y(h, null, E(k(p), (e) => (T(), _(f, {
				key: e.key,
				"data-testid": `showroom-settings-${e.key}`
			}, {
				header: A(() => [b("h3", P, O(e.title), 1)]),
				default: A(() => [b("dl", ne, [(T(!0), y(h, null, E(e.rows, (e) => (T(), y("div", {
					key: e.key,
					class: "flex justify-between gap-3",
					"data-row": e.key
				}, [b("dt", I, O(e.label), 1), b("dd", L, O(e.value), 1)], 8, F))), 128))])]),
				_: 2
			}, 1032, ["data-testid"]))), 128))])) : (T(), _(c, {
				key: 2,
				class: "h-24"
			}))]);
		};
	}
}), z = [
	"draft",
	"in_review",
	"approved",
	"archived"
];
function B(e) {
	switch (e) {
		case "in_review": return "info";
		case "approved": return "success";
		case "draft": return "primary";
		default: return "neutral";
	}
}
function V() {
	return l("showroom", "showroom.stats.show", async (e) => a(await e.GET("/stats")).data);
}
function H(e) {
	return `/showroom/products/${encodeURIComponent(e)}`;
}
function U(e) {
	return `/showroom/product-models/${encodeURIComponent(e)}`;
}
function W(e) {
	if (!e) return null;
	let t = new Date(e);
	return Number.isNaN(t.getTime()) ? e : t.toLocaleString();
}
// src/components/widgets/CompletenessWidget.vue?vue&type=script&setup=true&lang.ts
var G = {
	class: "h-full",
	"data-testid": "showroom-completeness"
}, K = {
	key: 1,
	class: "flex flex-col gap-3"
}, q = ["data-slot"], J = { class: "flex items-center justify-between gap-3 text-sm" }, Y = { class: "tabular-nums" }, X = { "data-testid": "showroom-completeness-average" }, Z = { class: "text-muted" }, re = {
	key: 3,
	class: "space-y-2"
}, ie = /* @__PURE__ */ C({
	__name: "CompletenessWidget",
	props: {
		data: {},
		settings: {}
	},
	setup(r) {
		let { t: i } = j(), { data: a, status: o, forbidden: s } = V(), c = g(() => a.value?.completeness ?? null);
		return (r, a) => {
			let l = t, u = e, d = n, g = f, v = p, C = m;
			return T(), y("div", G, [k(s) ? (T(), _(l, {
				key: 0,
				compact: ""
			})) : k(o) === "error" ? (T(), _(u, {
				key: 1,
				message: k(i)("atrium.widget.failed")
			}, null, 8, ["message"])) : k(c) ? (T(), y(h, { key: 2 }, [k(c).length === 0 ? (T(), _(d, {
				key: 0,
				compact: "",
				title: k(i)("showroom.widgets.no_completeness_yet"),
				"data-testid": "showroom-completeness-empty"
			}, null, 8, ["title"])) : (T(), y("ul", K, [(T(!0), y(h, null, E(k(c), (e) => (T(), y("li", {
				key: `${e.channel}-${e.locale}`,
				"data-testid": "showroom-completeness-row",
				"data-slot": `${e.channel}-${e.locale}`
			}, [b("div", J, [S(g, {
				class: "font-mono hover:underline",
				to: {
					path: "/showroom/products",
					query: {
						"complete[scope]": e.channel,
						"complete[locale]": e.locale,
						"complete[min]": "100"
					}
				}
			}, {
				default: A(() => [x(O(e.channel) + " · " + O(e.locale), 1)]),
				_: 2
			}, 1032, ["to"]), b("span", Y, [b("span", X, O(e.average) + "%", 1), b("span", Z, " — " + O(k(i)("showroom.widgets.fully_complete", {
				complete: e.complete,
				products: e.products
			})), 1)])]), S(v, {
				class: "mt-1",
				"model-value": e.average,
				max: 100,
				size: "sm"
			}, null, 8, ["model-value"])], 8, q))), 128))]))], 64)) : (T(), y("div", re, [S(C, { class: "h-4 w-2/3" }), S(C, { class: "h-4 w-1/2" })]))]);
		};
	}
}), Q = {
	class: "h-full",
	"data-testid": "showroom-product-status"
}, ae = {
	key: 2,
	class: "flex flex-wrap gap-2"
}, oe = { class: "text-sm font-semibold tabular-nums" }, se = { class: "text-sm font-semibold tabular-nums" }, ce = {
	key: 3,
	class: "flex gap-2"
}, le = /* @__PURE__ */ C({
	__name: "ProductStatusWidget",
	props: {
		data: {},
		settings: {}
	},
	setup(n) {
		let { t: r } = j(), { data: i, status: a, forbidden: o } = V(), s = g(() => i.value?.status ?? null);
		return (n, i) => {
			let c = t, l = e, d = u, p = f, g = m;
			return T(), y("div", Q, [k(o) ? (T(), _(c, {
				key: 0,
				compact: ""
			})) : k(a) === "error" ? (T(), _(l, {
				key: 1,
				message: k(r)("atrium.widget.failed")
			}, null, 8, ["message"])) : k(s) ? (T(), y("div", ae, [(T(!0), y(h, null, E(k(z), (e) => (T(), _(p, {
				key: e,
				to: {
					path: "/showroom/products",
					query: { status: e }
				},
				class: "flex items-center gap-2 rounded-md border border-default px-3 py-2 hover:bg-elevated",
				"data-testid": "showroom-product-status-count",
				"data-status": e
			}, {
				default: A(() => [S(d, {
					color: k(B)(e),
					variant: "subtle",
					size: "sm"
				}, {
					default: A(() => [x(O(k(r)(`showroom.statuses.${e}`)), 1)]),
					_: 2
				}, 1032, ["color"]), b("span", oe, O(k(s).counts[e] ?? 0), 1)]),
				_: 2
			}, 1032, ["to", "data-status"]))), 128)), S(p, {
				to: {
					path: "/showroom/products",
					query: { published: "1" }
				},
				class: "flex items-center gap-2 rounded-md border border-default px-3 py-2 hover:bg-elevated",
				"data-testid": "showroom-product-status-count",
				"data-status": "published"
			}, {
				default: A(() => [S(d, {
					color: "success",
					variant: "subtle",
					size: "sm"
				}, {
					default: A(() => [x(O(k(r)("showroom.widgets.live")), 1)]),
					_: 1
				}), b("span", se, O(k(s).published), 1)]),
				_: 1
			})])) : (T(), y("div", ce, [S(g, { class: "h-9 w-24" }), S(g, { class: "h-9 w-24" })]))]);
		};
	}
}), ue = {
	class: "h-full",
	"data-testid": "showroom-recent-changes"
}, de = {
	key: 1,
	class: "flex flex-col divide-y divide-default"
}, fe = ["data-version"], pe = { class: "flex items-center gap-2" }, me = { class: "font-mono text-muted" }, he = { class: "text-right text-muted" }, ge = {
	key: 3,
	class: "space-y-2"
}, _e = /* @__PURE__ */ C({
	__name: "RecentChangesWidget",
	props: {
		data: {},
		settings: {}
	},
	setup(r) {
		let { t: i } = j(), { data: a, status: o, forbidden: s } = V(), c = g(() => a.value?.recent_versions ?? null);
		function l(e) {
			return e.subject.key === null ? null : e.subject.type === "product" ? H(e.subject.id) : U(e.subject.key);
		}
		return (r, a) => {
			let d = t, p = e, g = n, C = f, w = u, D = m;
			return T(), y("div", ue, [k(s) ? (T(), _(d, {
				key: 0,
				compact: ""
			})) : k(o) === "error" ? (T(), _(p, {
				key: 1,
				message: k(i)("atrium.widget.failed")
			}, null, 8, ["message"])) : k(c) ? (T(), y(h, { key: 2 }, [k(c).length === 0 ? (T(), _(g, {
				key: 0,
				compact: "",
				title: k(i)("showroom.widgets.no_changes"),
				"data-testid": "showroom-recent-changes-empty"
			}, null, 8, ["title"])) : (T(), y("ul", de, [(T(!0), y(h, null, E(k(c), (e) => (T(), y("li", {
				key: `${e.subject.type}-${e.subject.id}-${e.version}`,
				class: "flex items-center justify-between gap-3 py-2 text-sm",
				"data-testid": "showroom-recent-change",
				"data-version": e.version
			}, [b("span", pe, [
				l(e) ? (T(), _(C, {
					key: 0,
					class: "font-mono hover:underline",
					to: l(e)
				}, {
					default: A(() => [x(O(e.subject.key), 1)]),
					_: 2
				}, 1032, ["to"])) : v("", !0),
				b("span", me, "v" + O(e.version), 1),
				S(w, {
					color: "neutral",
					variant: "subtle",
					size: "sm"
				}, {
					default: A(() => [x(O(e.action), 1)]),
					_: 2
				}, 1024)
			]), b("span", he, [e.author ? (T(), y(h, { key: 0 }, [x(O(k(i)("showroom.widgets.by", { author: String(e.author.id) })) + " · ", 1)], 64)) : v("", !0), x(" " + O(k(W)(e.created_at)), 1)])], 8, fe))), 128))]))], 64)) : (T(), y("div", ge, [S(D, { class: "h-4 w-2/3" }), S(D, { class: "h-4 w-1/2" })]))]);
		};
	}
}), ve = {
	class: "h-full",
	"data-testid": "showroom-review-queue"
}, ye = { class: "mb-2 text-sm text-muted" }, be = { class: "flex flex-col divide-y divide-default" }, xe = ["data-product"], Se = { class: "text-muted" }, Ce = {
	key: 3,
	class: "space-y-2"
}, we = /* @__PURE__ */ C({
	__name: "ReviewQueueWidget",
	props: {
		data: {},
		settings: {}
	},
	setup(r) {
		let { t: i } = j(), { data: a, status: o, forbidden: s } = V(), c = g(() => a.value?.review_queue ?? null);
		return (r, a) => {
			let l = t, u = e, d = n, p = f, g = m;
			return T(), y("div", ve, [k(s) ? (T(), _(l, {
				key: 0,
				compact: ""
			})) : k(o) === "error" ? (T(), _(u, {
				key: 1,
				message: k(i)("atrium.widget.failed")
			}, null, 8, ["message"])) : k(c) ? (T(), y(h, { key: 2 }, [k(c).products.length === 0 ? (T(), _(d, {
				key: 0,
				compact: "",
				title: k(i)("showroom.widgets.nothing_in_review"),
				"data-testid": "showroom-review-queue-empty"
			}, null, 8, ["title"])) : (T(), y(h, { key: 1 }, [b("p", ye, [S(p, {
				class: "hover:underline",
				to: {
					path: "/showroom/products",
					query: { status: "in_review" }
				},
				"data-testid": "showroom-review-queue-total"
			}, {
				default: A(() => [x(O(k(i)("showroom.widgets.waiting_review", { count: k(c).total })), 1)]),
				_: 1
			})]), b("ul", be, [(T(!0), y(h, null, E(k(c).products, (e) => (T(), y("li", {
				key: e.id,
				class: "flex items-center justify-between gap-3 py-2 text-sm",
				"data-testid": "showroom-review-queue-product",
				"data-product": e.identifier
			}, [S(p, {
				class: "font-mono hover:underline",
				to: k(H)(e.id)
			}, {
				default: A(() => [x(O(e.identifier), 1)]),
				_: 2
			}, 1032, ["to"]), b("span", Se, O(k(W)(e.updated_at)), 1)], 8, xe))), 128))])], 64))], 64)) : (T(), y("div", Ce, [S(g, { class: "h-4 w-1/3" }), S(g, { class: "h-4 w-2/3" })]))]);
		};
	}
}), Te = { showroom: {
	assets: {
		title: "Assets",
		grid: "Grid",
		list: "List",
		upload: "Upload an asset",
		upload_button: "Upload",
		file: "File",
		code_hint: "Optional; taken from the file name when left empty.",
		all_types: "All types",
		types: {
			images: "Images",
			pdf: "PDF",
			videos: "Videos"
		},
		product: "Product",
		empty: "No assets match these filters.",
		size: "Size",
		checksum: "Checksum",
		details: "Details",
		open_file: "Open file",
		replace_file: "Replace file",
		links: "Linked to",
		no_links: "This asset is not linked to anything yet.",
		link_types: {
			product: "Product",
			product_model: "Product model",
			owner: "Owner"
		},
		target: "Target",
		target_hint: "A product identifier, or a product model or owner code.",
		role: "Role",
		link: "Link",
		unlink: "Unlink",
		confirm_delete: "Delete this asset? Its links are removed too."
	},
	owners: {
		title: "Owners",
		description: "Brands, suppliers, stores and the other owners products belong to, as a tree.",
		flags: "Flags",
		new: "New owner",
		no_types: "Create an owner type first.",
		code_hint: "Lowercase letters, digits, dashes and underscores. Cannot be changed later.",
		choose_type: "Choose a type",
		parent_hint: "The parent owner's code; leave empty for a root.",
		all_types: "All types",
		empty: "No owners match these filters.",
		children: "Children",
		no_children: "No owners beneath this one yet.",
		add_child: "Add child",
		details: "Details",
		move_hint: "Change to move this owner, with everything beneath it. Empty to make it a root.",
		products: "Products",
		counts: "Owns {products} product(s) and {models} product model(s).",
		no_flags: "No flags apply to owners.",
		confirm_delete: "Delete this owner?"
	},
	owner_types: {
		title: "Owner types",
		description: "The kinds of owner, and the chain rules between them.",
		new: "New owner type",
		code_hint: "Lowercase letters, digits and underscores. Cannot be changed later.",
		empty: "No owner types yet.",
		rules: "Chain rules",
		parent_types: "Parent types",
		any_parent: "Any type may be the parent",
		any: "Any",
		no_types: "No owner types yet.",
		can_be_root: "May be a root",
		owns_products: "Owns products",
		owners: "Owners",
		owners_of_type: "Owners ({count})",
		confirm_delete: "Delete this owner type?"
	},
	transfers: {
		title: "Transfers",
		recent: "Recent transfers",
		flow: "Flow",
		status: "Status",
		started: "Started",
		finished: "Finished",
		no_runs: "No transfers yet.",
		runs_failed: "The transfers could not be loaded from Impex.",
		requires_impex: "Listing transfers requires Impex.",
		requires_impex_hint: "Imports and exports still start here; follow them once Impex is active.",
		started_notice: "Transfer queued ({flow}).",
		import: {
			title: "Import products",
			file: "File",
			file_hint: "A CSV or JSON Lines file.",
			mode: "Mode",
			modes: {
				upsert: "Create or update",
				create: "Create only",
				update: "Update only"
			},
			submit: "Start import"
		},
		export: {
			title: "Export products",
			format: "Format",
			formats: {
				jsonl: "JSON Lines",
				csv: "CSV"
			},
			all_families: "All families",
			category_tree: "Category tree",
			all_categories: "All categories",
			no_channel: "No channel",
			published: "Published copies only",
			submit: "Start export"
		}
	}
} }, Ee = { showroom: { catalog: {
	none: "—",
	yes: "Yes",
	no: "No",
	enabled: "Enabled",
	disabled: "Disabled",
	inherited: "inherited",
	add: "Add",
	create: "Create",
	save: "Save",
	delete: "Delete",
	remove: "Remove",
	state: {
		not_found: "Not found.",
		load_failed: "Showroom could not be loaded.",
		action_forbidden: "You may not do this."
	},
	pager: {
		previous: "Previous",
		next: "Next",
		page: "Page {current} of {last}"
	},
	filters: {
		search: "Search",
		filter: "Filter",
		clear: "Clear",
		all_statuses: "All statuses",
		all_families: "All families",
		all_family_variants: "All family variants"
	},
	fields: {
		identifier: "Identifier",
		code: "Code",
		label: "Label",
		label_in: "Label ({locale})",
		family: "Family",
		family_variant: "Family variant",
		parent: "Parent",
		owner: "Owner",
		category: "Category",
		enabled: "Status",
		workflow: "Workflow",
		updated: "Updated",
		slug: "Slug",
		locale: "Locale",
		channel: "Channel",
		type: "Type",
		group: "Group",
		sort_order: "Sort order"
	},
	products: {
		title: "Products",
		new: "New product",
		create: "Create product",
		empty: "No products found.",
		flag_definitions: "Flag definitions",
		identifier_hint: "Unique, and never changed once the product exists.",
		no_family: "No family",
		details: "Details",
		owner_hint: "An owner code; leave empty for none.",
		inherited_owner: "Owner (from its product model)",
		identifiers_and_flags: "Identifiers and flags",
		confirm_delete: "Delete this product? This cannot be undone."
	},
	product_models: {
		title: "Product models",
		new: "New product model",
		empty: "No product models found.",
		choose_family_variant: "Choose a family variant",
		level: "level {level}",
		variant_products: "Variant products",
		sub_models: "Sub-models",
		add_variant: "Add variant",
		add_sub_model: "Add sub-model",
		confirm_delete: "Delete this product model? This cannot be undone."
	},
	families: {
		title: "Families",
		new: "New family",
		create: "Create family",
		empty: "No families found.",
		code_hint: "Lowercase letters, digits and underscores, starting with a letter.",
		attributes_count: "Attributes",
		label_attribute: "Label attribute",
		label_attribute_hint: "The text attribute products of this family are labelled by.",
		no_label_attribute: "No label attribute",
		members: "Family attributes",
		no_attributes: "This family has no attributes yet.",
		required: "Required",
		required_channels: "Required on channels",
		required_channels_hint: "All channels",
		variants: "Family variants",
		level: "Level {level}",
		axes: "Axes",
		attributes: "Attributes",
		create_variant: "Create family variant",
		confirm_delete: "Delete this family? This cannot be undone."
	},
	family_variants: {
		levels: "Levels",
		level: "Level",
		common: "Common",
		confirm_delete: "Delete this family variant? This cannot be undone."
	},
	workflow: {
		title: "Workflow",
		published_version: "Published version",
		not_published: "Not published",
		comment: "Comment",
		completeness: "Completeness",
		no_completeness: "No completeness scores yet.",
		missing: "Missing",
		history: "History",
		no_history: "No history yet.",
		by: "by {author}",
		revert: "Revert",
		confirm_revert: "Revert the product to version {version}?",
		transitions: {
			submit: "Submit for review",
			approve: "Approve",
			reject: "Reject",
			publish: "Publish",
			unpublish: "Unpublish",
			archive: "Archive",
			restore: "Restore"
		}
	},
	values: {
		title: "Values",
		inherited: "Inherited values",
		none_settable: "No attributes can be set here.",
		slot_unavailable: "Add a locale or channel first.",
		switch: "Switch",
		amount: "Amount",
		unit: "Unit",
		add_attribute: "Add attribute",
		choose_attribute: "Choose an attribute"
	},
	categories: {
		title: "Categories",
		hint: "Category codes, separated by commas.",
		inherited: "Inherited categories"
	},
	external_ids: {
		title: "External ids",
		source: "New source",
		source_hint: "Lowercase letters, digits, dashes and underscores.",
		id: "External id",
		synced: "synced {time}",
		never_synced: "never synced"
	},
	flags: {
		own: "Own flags",
		effective: "Effective flags",
		none: "No flags apply to products."
	},
	assets: {
		title: "Assets",
		none: "No assets here yet.",
		upload_and_link: "Upload and link",
		role: "Role",
		upload: "Upload"
	},
	associations: {
		title: "Associations",
		none: "No associations.",
		type: "Association type",
		choose_type: "Choose a type",
		target_kind: "Target kind",
		product: "Product",
		product_model: "Product model",
		target: "Target",
		quantity: "Quantity",
		quantity_hint: "For quantified types.",
		add: "Add",
		remove: "Remove"
	}
} } }, De = { showroom: { structure: {
	code_hint: "Lowercase letters, digits and underscores. Cannot be changed later.",
	details: "Details",
	attributes: {
		title: "Attributes",
		new: "New attribute",
		create: "Create attribute",
		all_types: "All types",
		all_groups: "All groups",
		empty: "No attributes match these filters.",
		switches: "Switches",
		is_unique: "Unique",
		is_localizable: "Localizable",
		is_scopable: "Scopable",
		type_hint: "Cannot be changed later.",
		choose_type: "Choose a type",
		no_group: "No group",
		settings: "Settings (JSON)",
		settings_hint: "Type-specific validation, such as max_length. Metric attributes need metric_family and default_unit.",
		invalid_settings: "Settings must be a JSON object.",
		options: "Options",
		no_options: "This attribute has no options yet.",
		add_option: "Add option",
		confirm_delete: "Delete this attribute?",
		confirm_delete_option: "Delete the option {option}?"
	},
	groups: {
		title: "Attribute groups",
		new: "New attribute group",
		create: "Create group",
		empty: "No attribute groups yet.",
		attributes_count: "Attributes",
		members: "Attributes in this group",
		no_members: "No attributes in this group yet. An attribute joins a group from its own page.",
		confirm_delete: "Delete this attribute group?"
	},
	association_types: {
		title: "Association types",
		description: "How products relate: cross-sell, accessories, compatible parts (two-way), bundles and kits (quantified).",
		new: "New association type",
		empty: "No association types yet.",
		two_way: "Two-way",
		quantified: "Quantified",
		associations: "Associations",
		confirm_delete: "Delete the association type {code}?"
	},
	categories: {
		title: "Categories",
		description: "Independent category trees: each root category is a tree.",
		new_tree: "New category tree",
		empty: "No category trees yet.",
		children: "Children",
		chain: "Category path",
		branch: "Branch",
		no_branch: "No categories beneath this one yet.",
		add_child: "Add child",
		products: "Products",
		move_hint: "Change to move this category, with everything beneath it. Empty to make it a tree.",
		filed_counts: "{products} product(s) and {models} product model(s) are filed here.",
		confirm_delete: "Delete this category and everything beneath it?"
	},
	channels: {
		title: "Channels",
		description: "Where products are published, in which locales and currencies, and the locales content is written in.",
		new: "New channel",
		empty: "No channels yet.",
		locales: "Locales",
		no_locales: "No locales yet.",
		locale_code_hint: "A language, optionally with a region: en, en_US.",
		add_locale: "Add locale",
		currencies: "Currencies",
		currencies_hint: "ISO codes, separated by commas: USD, EUR.",
		category_tree: "Category tree",
		delete_note: "Deleting a channel also removes every value scoped to it.",
		confirm_delete: "Delete this channel, and every value scoped to it?",
		confirm_delete_locale: "Delete the locale {code}?"
	},
	flags: {
		title: "Flags",
		description: "Commercial markers (restricted, pricing only, clearance) that products and owners carry. An owner's flags reach everything beneath it.",
		new: "New flag",
		empty: "No flags yet.",
		applies_to: "Applies to",
		subject_product: "Products",
		subject_owner: "Owners",
		delete_note: "A flag still assigned to a product or owner cannot be deleted; remove it from them first.",
		confirm_delete: "Delete this flag?"
	}
} } }, Oe = { showroom: {
	statuses: {
		draft: "Draft",
		in_review: "In review",
		approved: "Approved",
		archived: "Archived"
	},
	widgets: {
		live: "Live",
		no_completeness_yet: "No completeness scores yet. They need families with required attributes and at least one channel.",
		fully_complete: "{complete} of {products} complete",
		nothing_in_review: "Nothing is waiting for review.",
		waiting_review: "{count} waiting",
		no_changes: "No changes yet.",
		by: "by {author}"
	},
	settings: {
		api: "HTTP API",
		mcp: "MCP",
		authorization: "Authorization",
		routes_prefix: "Route prefix",
		per_page: "Page size",
		max_per_page: "Maximum page size",
		web_transport: "Web transport",
		local_transport: "Local transport",
		on: "On",
		off: "Off"
	},
	search: { groups: {
		products: "Products",
		assets: "Assets",
		categories: "Categories",
		owners: "Owners",
		families: "Families",
		attributes: "Attributes",
		attribute_groups: "Attribute groups"
	} }
} }, ke = [
	{
		path: "/showroom/assets/:asset",
		name: "showroom-assets-asset",
		component: () => import("./chunks/_asset_-k1rO1sYf.js")
	},
	{
		path: "/showroom/assets",
		name: "showroom-assets",
		component: () => import("./chunks/assets-CmbWjfW5.js")
	},
	{
		path: "/showroom/association-types",
		name: "showroom-association-types",
		component: () => import("./chunks/association-types-ENAiHbUt.js")
	},
	{
		path: "/showroom/attribute-groups/:group",
		name: "showroom-attribute-groups-group",
		component: () => import("./chunks/_group_-BZ9Mwl81.js")
	},
	{
		path: "/showroom/attribute-groups",
		name: "showroom-attribute-groups",
		component: () => import("./chunks/attribute-groups-CrdTnC1o.js")
	},
	{
		path: "/showroom/attributes/:attribute",
		name: "showroom-attributes-attribute",
		component: () => import("./chunks/_attribute_-C98nvyar.js")
	},
	{
		path: "/showroom/attributes/create",
		name: "showroom-attributes-create",
		component: () => import("./chunks/create-Dx6jfGCh.js")
	},
	{
		path: "/showroom/attributes",
		name: "showroom-attributes",
		component: () => import("./chunks/attributes-B5CfMT4x.js")
	},
	{
		path: "/showroom/categories/:category",
		name: "showroom-categories-category",
		component: () => import("./chunks/_category_-MXoJrNTl.js")
	},
	{
		path: "/showroom/categories",
		name: "showroom-categories",
		component: () => import("./chunks/categories-C7V3VxRQ.js")
	},
	{
		path: "/showroom/channels/:channel",
		name: "showroom-channels-channel",
		component: () => import("./chunks/_channel_-CMukUFgZ.js")
	},
	{
		path: "/showroom/channels",
		name: "showroom-channels",
		component: () => import("./chunks/channels-DxwqyaIL.js")
	},
	{
		path: "/showroom/families/:family",
		name: "showroom-families-family",
		component: () => import("./chunks/_family_-DYhoxvbv.js")
	},
	{
		path: "/showroom/families",
		name: "showroom-families",
		component: () => import("./chunks/families-Bpc1bYES.js")
	},
	{
		path: "/showroom/family-variants/:familyVariant",
		name: "showroom-family-variants-familyVariant",
		component: () => import("./chunks/_familyVariant_-BwEssoQw.js")
	},
	{
		path: "/showroom/flags/:flag",
		name: "showroom-flags-flag",
		component: () => import("./chunks/_flag_-DWple0yL.js")
	},
	{
		path: "/showroom/flags",
		name: "showroom-flags",
		component: () => import("./chunks/flags-DBv1TMhJ.js")
	},
	{
		path: "/showroom/owner-types/:ownerType",
		name: "showroom-owner-types-ownerType",
		component: () => import("./chunks/_ownerType_-CZxt_RLp.js")
	},
	{
		path: "/showroom/owner-types",
		name: "showroom-owner-types",
		component: () => import("./chunks/owner-types-TeDuQkog.js")
	},
	{
		path: "/showroom/owners/:owner",
		name: "showroom-owners-owner",
		component: () => import("./chunks/_owner_-DrctNbCH.js")
	},
	{
		path: "/showroom/owners",
		name: "showroom-owners",
		component: () => import("./chunks/owners-DJShwr7M.js")
	},
	{
		path: "/showroom/product-models/:productModel",
		name: "showroom-product-models-productModel",
		component: () => import("./chunks/_productModel_-B1Ir8rM7.js")
	},
	{
		path: "/showroom/product-models",
		name: "showroom-product-models",
		component: () => import("./chunks/product-models-B3iMht11.js")
	},
	{
		path: "/showroom/products/:product",
		name: "showroom-products-product",
		component: () => import("./chunks/_product_-CRimsIrh.js")
	},
	{
		path: "/showroom/products/create",
		name: "showroom-products-create",
		component: () => import("./chunks/create-CgGkAsOI.js")
	},
	{
		path: "/showroom/products",
		name: "showroom-products",
		component: () => import("./chunks/products-DbRBKRtP.js")
	},
	{
		path: "/showroom/transfers",
		name: "showroom-transfers",
		component: () => import("./chunks/transfers-9jGU2loy.js")
	}
], $ = {
	"showroom.products": "showroom-products",
	"showroom.product-create": "showroom-products-create",
	"showroom.product": "showroom-products-product",
	"showroom.product-models": "showroom-product-models",
	"showroom.product-model": "showroom-product-models-productModel",
	"showroom.families": "showroom-families",
	"showroom.family": "showroom-families-family",
	"showroom.family-variant": "showroom-family-variants-familyVariant",
	"showroom.attributes": "showroom-attributes",
	"showroom.attribute-create": "showroom-attributes-create",
	"showroom.attribute": "showroom-attributes-attribute",
	"showroom.attribute-groups": "showroom-attribute-groups",
	"showroom.attribute-group": "showroom-attribute-groups-group",
	"showroom.association-types": "showroom-association-types",
	"showroom.categories": "showroom-categories",
	"showroom.category": "showroom-categories-category",
	"showroom.channels": "showroom-channels",
	"showroom.channel": "showroom-channels-channel",
	"showroom.owners": "showroom-owners",
	"showroom.owner": "showroom-owners-owner",
	"showroom.owner-types": "showroom-owner-types",
	"showroom.owner-type": "showroom-owner-types-ownerType",
	"showroom.flags": "showroom-flags",
	"showroom.flag": "showroom-flags-flag",
	"showroom.assets": "showroom-assets",
	"showroom.asset": "showroom-assets-asset",
	"showroom.transfers": "showroom-transfers"
}, Ae = i({
	key: "showroom",
	routes: ke,
	screens: $,
	widgets: {
		"showroom.product-status": le,
		"showroom.completeness": ie,
		"showroom.review-queue": we,
		"showroom.recent-changes": _e
	},
	settingsPanels: { showroom: R },
	searchSources: { showroom: ee },
	messages: { en: o(Te, Ee, De, Oe) }
});
export { Ae as default, $ as screens };
